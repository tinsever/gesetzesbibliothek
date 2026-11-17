<?php
namespace wcf\acp\form;

use Laminas\Diactoros\Response\RedirectResponse;
use wcf\form\AbstractForm;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\UserInputException;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\DateFormField;
use wcf\system\form\builder\field\HiddenFormField;
use wcf\system\form\builder\field\SingleSelectionFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\lawLibrary\LawLibrary;
use wcf\system\lawLibrary\LawLibraryRepository;
use wcf\system\lawLibrary\LawLibraryRequest;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

final class LawAmendmentEditForm extends AbstractFormBuilderForm
{
    public $neededPermissions = ['admin.content.lawLibrary.canManage'];
    public $activeMenuItem = 'wcf.acp.menu.link.lawLibrary.laws';
    public $templateName = 'lawAmendmentEdit';
    private array $snapshot;
    private array $law;
    private ?array $amendment = null;
    private string $amendmentId = '';

    public function readParameters()
    {
        parent::readParameters();
        $this->snapshot = LawLibraryRepository::read();
        if (!\is_string($_GET['lawKey'] ?? null) || !\is_string($_GET['amendmentId'] ?? '')) throw new IllegalLinkException();
        $laws = LawLibraryRepository::numbered($this->snapshot['data']);
        if (!isset($laws[$_GET['lawKey']])) throw new IllegalLinkException();
        $this->law = $laws[$_GET['lawKey']];
        $this->amendmentId = $_GET['amendmentId'] ?? '';
        foreach ($this->law['amendments'] as $entry) {
            if ($entry['id'] === $this->amendmentId) $this->amendment = $entry;
        }
        if ($this->amendmentId !== '' && $this->amendment === null) throw new IllegalLinkException();
    }

    public function readFormParameters()
    {
        LawLibraryRequest::validatePost(['revision', 'date', 'amendingLawKey', 'deleteAmendment'], true);
        parent::readFormParameters();
    }

    public function validate()
    {
        parent::validate();
        if (!LawLibrary::validDate($_POST['date'] ?? null)) {
            $this->form->getNodeById('date')->addValidationError(new FormFieldValidationError('invalid', 'wcf.lawLibrary.error.date'));
            throw new UserInputException('date', 'invalid');
        }
    }

    protected function createForm()
    {
        parent::createForm();
        $sources = ['' => 'wcf.global.noSelection'];
        foreach (LawLibraryRepository::numbered($this->snapshot['data']) as $source) {
            if ($source['id'] !== $this->law['id']) $sources[$source['id']] = $source['number'] . ' · ' . $source['title'];
        }
        $this->form->appendChildren([
            HiddenFormField::create('revision')->value((string) $this->snapshot['revision']),
            FormContainer::create('general')->label('wcf.lawLibrary.history')->appendChildren([
                DateFormField::create('date')->label('wcf.lawLibrary.amendment.date')->saveValueFormat('Y-m-d')->required()->value($this->amendment['date'] ?? $this->law['updated']),
                SingleSelectionFormField::create('amendingLawKey')->label('wcf.lawLibrary.amendment.law')->description('wcf.lawLibrary.amendment.source.description')->options($sources)->required()->value($this->amendment['lawKey'] ?? ''),
            ]),
        ]);
        if ($this->amendment !== null) {
            $this->form->appendChild(FormContainer::create('deleteSection')->label('wcf.lawLibrary.amendment.delete')->appendChild(
                BooleanFormField::create('deleteAmendment')->label('wcf.lawLibrary.amendment.delete.confirm')->description('wcf.lawLibrary.amendment.delete.description')->value(false)
            ));
        }
    }

    protected function setFormAction()
    {
        $this->form->action(LinkHandler::getInstance()->getLink('LawAmendmentEdit', ['lawKey' => $this->law['id'], 'amendmentId' => $this->amendmentId]));
    }

    public function save()
    {
        AbstractForm::save();
        $values = $this->form->getData()['data'];
        $revision = $this->form->getNodeById('revision')->getValue();
        if (!\is_string($revision) || !\ctype_digit($revision)) throw new UserInputException('revision');
        try {
            LawLibraryRepository::save([
                'action' => !empty($values['deleteAmendment']) ? 'deleteAmendment' : 'saveAmendment',
                'lawKey' => $this->law['id'], 'id' => $this->amendmentId ?: \bin2hex(\random_bytes(16)),
                'date' => $values['date'], 'amendingLawKey' => $values['amendingLawKey'],
            ], (int) $revision);
        } catch (\RuntimeException $exception) {
            $this->form->getNodeById('date')->addValidationError(new FormFieldValidationError('conflict', 'wcf.lawLibrary.error.conflict'));
            throw new UserInputException('date', 'conflict');
        } catch (\InvalidArgumentException $exception) {
            $message = $exception->getMessage() === 'duplicateAmendment' ? 'duplicate' : 'invalid';
            $this->form->getNodeById('amendingLawKey')->addValidationError(new FormFieldValidationError('invalid', 'wcf.lawLibrary.amendment.error.' . $message));
            throw new UserInputException('amendingLawKey', 'invalid');
        }
        $this->saved();
        return new RedirectResponse(LinkHandler::getInstance()->getLink('LawEdit', ['lawKey' => $this->law['id'], 'saved' => 1]), 303);
    }

    public function assignVariables()
    {
        parent::assignVariables();
        WCF::getTPL()->assign(['editedLaw' => $this->law, 'editedAmendment' => $this->amendment]);
    }
}
