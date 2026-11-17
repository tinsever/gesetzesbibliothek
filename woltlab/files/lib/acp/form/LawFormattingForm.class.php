<?php
namespace wcf\acp\form;

use Laminas\Diactoros\Response\RedirectResponse;
use wcf\form\AbstractForm;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\UserInputException;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\field\HiddenFormField;
use wcf\system\form\builder\field\SingleSelectionFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\lawLibrary\LawLibraryRepository;
use wcf\system\lawLibrary\LawLibraryRequest;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

final class LawFormattingForm extends AbstractFormBuilderForm
{
    public $neededPermissions = ['admin.content.lawLibrary.canManage'];
    public $activeMenuItem = 'wcf.acp.menu.link.lawLibrary.laws';
    public $templateName = 'lawFormatting';
    private array $snapshot;
    private array $law;

    public function readParameters()
    {
        parent::readParameters();
        $this->snapshot = LawLibraryRepository::read();
        $key = $_GET['lawKey'] ?? null;
        if (!\is_string($key)) throw new IllegalLinkException();
        $laws = LawLibraryRepository::numbered($this->snapshot['data']);
        if (!isset($laws[$key])) throw new IllegalLinkException();
        $this->law = $laws[$key];
    }

    public function readFormParameters()
    {
        LawLibraryRequest::validatePost(['revision', 'listStyle'], true);
        if (!\in_array($_POST['listStyle'] ?? null, ['default', 'upper-alpha'], true)) throw new IllegalLinkException();
        parent::readFormParameters();
    }

    protected function createForm()
    {
        parent::createForm();
        $this->form->appendChildren([
            HiddenFormField::create('revision')->value((string) $this->snapshot['revision']),
            FormContainer::create('general')->label('wcf.lawLibrary.formatting')->appendChild(
                SingleSelectionFormField::create('listStyle')->label('wcf.lawLibrary.listStyle')->description('wcf.lawLibrary.listStyle.description')->options(['default' => 'wcf.lawLibrary.listStyle.default', 'upper-alpha' => 'wcf.lawLibrary.listStyle.letters'])->value($this->law['listStyle'] ?? 'default')
            ),
        ]);
    }

    protected function setFormAction()
    {
        $this->form->action(LinkHandler::getInstance()->getLink('LawFormatting', ['lawKey' => $this->law['id']]));
    }

    public function save()
    {
        AbstractForm::save();
        $revision = $this->form->getNodeById('revision')->getValue();
        if (!\is_string($revision) || !\ctype_digit($revision)) throw new UserInputException('revision');
        try {
            LawLibraryRepository::save(['action' => 'setListStyle', 'lawKey' => $this->law['id'], 'listStyle' => $this->form->getData()['data']['listStyle']], (int) $revision);
        } catch (\RuntimeException $exception) {
            $this->form->getNodeById('listStyle')->addValidationError(new FormFieldValidationError('conflict', 'wcf.lawLibrary.error.conflict'));
            throw new UserInputException('listStyle', 'conflict');
        } catch (\InvalidArgumentException $exception) {
            $this->form->getNodeById('listStyle')->addValidationError(new FormFieldValidationError('invalid', 'wcf.lawLibrary.listStyle.error'));
            throw new UserInputException('listStyle', 'invalid');
        }
        $this->saved();
        return new RedirectResponse(LinkHandler::getInstance()->getLink('Law', ['isACP' => false, 'lawKey' => $this->law['id']]), 303);
    }

    public function assignVariables()
    {
        parent::assignVariables();
        WCF::getTPL()->assign('editedLaw', $this->law);
    }
}
