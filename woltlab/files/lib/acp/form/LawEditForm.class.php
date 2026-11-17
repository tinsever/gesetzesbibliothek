<?php
namespace wcf\acp\form;

use wcf\form\AbstractForm;
use wcf\form\AbstractFormBuilderForm;

use Laminas\Diactoros\Response\RedirectResponse;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\field\DateFormField;
use wcf\system\form\builder\field\HiddenFormField;
use wcf\system\form\builder\field\SingleSelectionFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\wysiwyg\WysiwygFormField;
use wcf\system\form\builder\field\dependency\ValueFormFieldDependency;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\lawLibrary\LawLibraryRepository;
use wcf\system\lawLibrary\LawLibrary;
use wcf\system\lawLibrary\LawLibraryRequest;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

final class LawEditForm extends AbstractFormBuilderForm
{
    public $neededPermissions = ['admin.content.lawLibrary.canManage'];
    public $activeMenuItem = 'wcf.acp.menu.link.lawLibrary.laws';
    public $loginRequired = true;
    public $templateName = 'lawEdit';
    private array $snapshot;
    private ?array $law = null;
    private string $lawKey = '';
    private string $categoryId = '';

    public function readParameters()
    {
        parent::readParameters();
        if (!LawLibraryRepository::canManage()) throw new PermissionDeniedException();
        $this->snapshot = LawLibraryRepository::read();
        $key = $_GET['lawKey'] ?? '';
        if (!\is_string($key)) throw new IllegalLinkException();
        $this->lawKey = $key;
        if ($key !== '') {
            $this->law = LawLibraryRepository::numbered($this->snapshot['data'])[$key] ?? null;
            if ($this->law === null) throw new IllegalLinkException();
        }
        $category = $_GET['categoryId'] ?? '';
        if ($category === '') $category = $this->snapshot['data']['categories'][0]['id'];
        $this->categoryId = $this->law['categoryId'] ?? (\is_string($category) ? $category : '');
        if (!\in_array($this->categoryId, \array_column($this->snapshot['data']['categories'], 'id'), true)) throw new IllegalLinkException();
    }

    public function readFormParameters()
    {
        LawLibraryRequest::validatePost(['revision', 'title', 'abbreviation', 'date', 'updated', 'amendingLawKey', 'listStyle', 'status', 'categoryId', 'lawText'], true);
        parent::readFormParameters();
    }

    public function validate()
    {
        parent::validate();
        foreach (['date', 'updated'] as $field) {
            if (!LawLibrary::validDate($_POST[$field] ?? '')) {
                $this->form->getNodeById($field)->addValidationError(new FormFieldValidationError('invalidDate', 'wcf.lawLibrary.error.date'));
                throw new UserInputException($field, 'invalidDate');
            }
        }
    }

    protected function createForm()
    {
        parent::createForm();
        $options = [];
        foreach ($this->snapshot['data']['categories'] as $category) $options[$category['id']] = ($category['base'] / 100) . 'xx · ' . $category['title'];
        $container = FormContainer::create('general')->label('wcf.lawLibrary.details')->appendChildren([
            TextFormField::create('title')->label('wcf.lawLibrary.lawTitle')->required()->maximumLength(250)->value($this->law['title'] ?? ''),
            TextFormField::create('abbreviation')->label('wcf.lawLibrary.abbreviation')->maximumLength(30)->value($this->law['abbreviation'] ?? ''),
            DateFormField::create('date')->label('wcf.lawLibrary.date')->saveValueFormat('Y-m-d')->required()->value($this->law['date'] ?? \gmdate('Y-m-d')),
            SingleSelectionFormField::create('status')->label('wcf.lawLibrary.status')->options(['active' => 'wcf.lawLibrary.status.active', 'announced' => 'wcf.lawLibrary.status.announced', 'repealed' => 'wcf.lawLibrary.status.repealed'])->value($this->law['status'] ?? 'active'),
            SingleSelectionFormField::create('categoryId')->label('wcf.lawLibrary.category')->options($options)->value($this->categoryId),
        ]);
        foreach ($this->snapshot['data']['categories'] as $category) {
            $options = ['' => 'wcf.lawLibrary.atEnd'];
            $siblings = \array_values(\array_filter(LawLibraryRepository::numbered($this->snapshot['data']), static fn ($law) => $law['categoryId'] === $category['id']));
            $beforeId = '';
            foreach ($siblings as $i => $law) {
                if ($law['id'] !== $this->lawKey) $options[$law['id']] = $law['number'] . ' · ' . $law['title'];
                elseif (isset($siblings[$i + 1])) $beforeId = $siblings[$i + 1]['id'];
            }
            $container->appendChild(SingleSelectionFormField::create('before_' . $category['id'])
                ->label('wcf.lawLibrary.insertBefore')->description('wcf.lawLibrary.numbering.description')->options($options)->value($beforeId)
                ->addDependency(ValueFormFieldDependency::create('category_' . $category['id'])->fieldId('categoryId')->values([$category['id']])));
        }
        $content = $this->law['content'] ?? '';
        $sources = ['' => 'wcf.lawLibrary.amendment.none'];
        foreach (LawLibraryRepository::numbered($this->snapshot['data']) as $source) {
            if ($source['id'] !== $this->lawKey) $sources[$source['id']] = $source['number'] . ' · ' . $source['title'];
        }
        if ($this->law && ($this->law['format'] ?? 'plain') === 'plain') $content = '<p>' . \nl2br(StringUtil::encodeHTML($content)) . '</p>';
        $this->form->appendChildren([
            HiddenFormField::create('revision')->value((string) $this->snapshot['revision']),
            $container,
            FormContainer::create('formatting')->label('wcf.lawLibrary.formatting')->appendChild(
                SingleSelectionFormField::create('listStyle')->label('wcf.lawLibrary.listStyle')->description('wcf.lawLibrary.listStyle.description')->options(['default' => 'wcf.lawLibrary.listStyle.default', 'upper-alpha' => 'wcf.lawLibrary.listStyle.letters'])->value($this->law['listStyle'] ?? 'default')
            ),
            FormContainer::create('amendment')->label('wcf.lawLibrary.updated')->appendChildren([
                DateFormField::create('updated')->label('wcf.lawLibrary.updated')->description('wcf.lawLibrary.updated.description')->saveValueFormat('Y-m-d')->required()->value($this->law['updated'] ?? \gmdate('Y-m-d')),
                SingleSelectionFormField::create('amendingLawKey')->label('wcf.lawLibrary.amendment.law')->description('wcf.lawLibrary.amendment.quick.description')->options($sources)->value(''),
            ]),
            FormContainer::create('text')->appendChild(WysiwygFormField::create('lawText')->objectProperty('content')->label('wcf.lawLibrary.text')->objectType('de.eulenthal.wcf.lawLibrary.message')->required()->maximumLength(200000)->value($content)),
        ]);
    }

    protected function setFormAction()
    {
        $this->form->action(LinkHandler::getInstance()->getLink('LawEdit', ['lawKey' => $this->lawKey, 'categoryId' => $this->categoryId]));
    }

    public function save()
    {
        AbstractForm::save();
        $values = $this->form->getData()['data'];
        $revision = $this->form->getNodeById('revision')->getValue();
        if (!\is_string($revision) || !\ctype_digit($revision)) throw new UserInputException('revision');
        $id = $this->lawKey ?: \bin2hex(\random_bytes(16));
        $law = \array_intersect_key($values, \array_flip(['title', 'abbreviation', 'date', 'updated', 'amendingLawKey', 'listStyle', 'status', 'categoryId', 'content']));
        $law['id'] = $id;
        $law['format'] = 'html';
        try {
            LawLibraryRepository::save(['action' => 'save', 'law' => $law, 'beforeId' => $values['before_' . $law['categoryId']] ?? ''], (int) $revision);
        } catch (\RuntimeException $exception) {
            $this->form->getNodeById('title')->addValidationError(new FormFieldValidationError('conflict', 'wcf.lawLibrary.error.conflict'));
            throw new UserInputException('title', 'conflict');
        } catch (\InvalidArgumentException $exception) {
            if (\in_array($exception->getMessage(), ['updatedBeforeHistory', 'updated', 'amendingLawKey'], true)) {
                $field = $exception->getMessage() === 'amendingLawKey' ? 'amendingLawKey' : 'updated';
                $message = $exception->getMessage() === 'updatedBeforeHistory' ? 'updatedBeforeHistory' : 'invalid';
                $this->form->getNodeById($field)->addValidationError(new FormFieldValidationError('invalid', 'wcf.lawLibrary.amendment.error.' . $message));
                throw new UserInputException($field, 'invalid');
            }
            $this->form->getNodeById('categoryId')->addValidationError(new FormFieldValidationError('invalid', 'wcf.lawLibrary.error.invalid'));
            throw new UserInputException('categoryId', 'invalid');
        }
        $this->saved();
        return new RedirectResponse(LinkHandler::getInstance()->getLink('LawList', ['saved' => 1]), 303);
    }

    public function assignVariables()
    {
        parent::assignVariables();
        WCF::getTPL()->assign('selectedCategory', $this->categoryId);
        WCF::getTPL()->assign('editedLaw', $this->law);
    }
}
