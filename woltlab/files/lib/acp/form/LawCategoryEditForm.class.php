<?php
namespace wcf\acp\form;

use Laminas\Diactoros\Response\RedirectResponse;
use wcf\form\AbstractForm;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\UserInputException;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\HiddenFormField;
use wcf\system\form\builder\field\IntegerFormField;
use wcf\system\form\builder\field\SingleSelectionFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\MultilineTextFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\lawLibrary\LawLibraryRepository;
use wcf\system\lawLibrary\LawLibraryRequest;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

final class LawCategoryEditForm extends AbstractFormBuilderForm
{
    public $neededPermissions = ['admin.content.lawLibrary.canManage'];
    public $activeMenuItem = 'wcf.acp.menu.link.lawLibrary.categories';
    public $templateName = 'lawCategoryEdit';
    private array $snapshot;
    private ?array $category = null;
    private string $categoryId = '';

    public function readParameters()
    {
        parent::readParameters();
        $this->snapshot = LawLibraryRepository::read();
        $id = $_GET['categoryId'] ?? '';
        if (!\is_string($id)) throw new IllegalLinkException();
        $this->categoryId = $id;
        foreach ($this->snapshot['data']['categories'] as $category) {
            if ($category['id'] === $id) $this->category = $category;
        }
        if ($id !== '' && $this->category === null) throw new IllegalLinkException();
    }

    public function readFormParameters()
    {
        LawLibraryRequest::validatePost(['revision', 'title', 'description', 'numberBlock', 'beforeId', 'deleteCategory'], true);
        parent::readFormParameters();
    }

    protected function createForm()
    {
        parent::createForm();
        $categories = $this->snapshot['data']['categories'];
        $beforeId = '';
        $options = ['' => 'wcf.lawLibrary.category.atEnd'];
        foreach ($categories as $index => $category) {
            if ($category['id'] !== $this->categoryId) $options[$category['id']] = ($category['base'] / 100) . 'xx · ' . $category['title'];
            elseif (isset($categories[$index + 1])) $beforeId = $categories[$index + 1]['id'];
        }
        $block = 1;
        while (\in_array($block * 100, \array_column($categories, 'base'), true)) ++$block;
        $this->form->appendChildren([
            HiddenFormField::create('revision')->value((string) $this->snapshot['revision']),
            FormContainer::create('general')->label('wcf.lawLibrary.category.details')->appendChildren([
                TextFormField::create('title')->label('wcf.lawLibrary.category.name')->required()->maximumLength(250)->value($this->category['title'] ?? ''),
                MultilineTextFormField::create('description')->rows(4)->label('wcf.lawLibrary.category.description')->maximumLength(2000)->value($this->category['description'] ?? ''),
                IntegerFormField::create('numberBlock')->label('wcf.lawLibrary.category.numberBlock')->description('wcf.lawLibrary.category.numberBlock.description')->required()->minimum(1)->maximum(9999)->value($this->category ? (int) ($this->category['base'] / 100) : $block),
                SingleSelectionFormField::create('beforeId')->label('wcf.lawLibrary.insertBefore')->description('wcf.lawLibrary.category.order.description')->options($options)->value($beforeId),
            ]),
        ]);
        if ($this->category !== null) {
            $this->form->appendChild(FormContainer::create('deleteSection')->label('wcf.lawLibrary.category.delete')->appendChild(
                BooleanFormField::create('deleteCategory')->label('wcf.lawLibrary.category.delete.confirm')->description('wcf.lawLibrary.category.delete.description')->value(false)
            ));
        }
    }

    protected function setFormAction()
    {
        $this->form->action(LinkHandler::getInstance()->getLink('LawCategoryEdit', ['categoryId' => $this->categoryId]));
    }

    public function save()
    {
        AbstractForm::save();
        $values = $this->form->getData()['data'];
        $revision = $this->form->getNodeById('revision')->getValue();
        if (!\is_string($revision) || !\ctype_digit($revision)) throw new UserInputException('revision');
        $command = [
            'action' => !empty($values['deleteCategory']) ? 'deleteCategory' : 'saveCategory',
            'id' => $this->categoryId ?: 'category_' . \bin2hex(\random_bytes(8)),
            'title' => $values['title'], 'description' => $values['description'],
            'base' => $values['numberBlock'] * 100, 'beforeId' => $values['beforeId'],
        ];
        try {
            LawLibraryRepository::save($command, (int) $revision);
        } catch (\RuntimeException $exception) {
            $this->form->getNodeById('title')->addValidationError(new FormFieldValidationError('conflict', 'wcf.lawLibrary.error.conflict'));
            throw new UserInputException('title', 'conflict');
        } catch (\InvalidArgumentException $exception) {
            $type = $exception->getMessage();
            $field = match ($type) {
                'base', 'duplicateBase' => 'numberBlock',
                'description' => 'description',
                'beforeId' => 'beforeId',
                'categoryNotEmpty', 'lastCategory' => 'deleteCategory',
                default => 'title',
            };
            $message = \in_array($type, ['duplicateBase', 'categoryNotEmpty', 'lastCategory', 'categoryLimit'], true) ? $type : 'invalid';
            $this->form->getNodeById($field)->addValidationError(new FormFieldValidationError('invalid', 'wcf.lawLibrary.category.error.' . $message));
            throw new UserInputException($field, 'invalid');
        }
        $this->saved();
        return new RedirectResponse(LinkHandler::getInstance()->getLink('LawCategoryList', ['saved' => 1]), 303);
    }

    public function assignVariables()
    {
        parent::assignVariables();
        WCF::getTPL()->assign('editedCategory', $this->category);
    }
}
