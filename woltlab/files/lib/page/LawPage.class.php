<?php
namespace wcf\page;

use wcf\system\exception\IllegalLinkException;
use wcf\system\lawLibrary\LawHtmlOutputProcessor;
use wcf\system\lawLibrary\LawLibraryRepository;
use wcf\system\WCF;
use wcf\util\StringUtil;

final class LawPage extends AbstractPage
{
    public $neededPermissions = ['user.lawLibrary.canView'];
    public $templateName = 'law';
    private string $lawKey = '';
    private array $snapshot;
    private array $law;

    public function readParameters()
    {
        parent::readParameters();
        if (!\is_string($_GET['lawKey'] ?? null)) throw new IllegalLinkException();
        $this->lawKey = $_GET['lawKey'];
    }

    public function readData()
    {
        parent::readData();
        $this->snapshot = LawLibraryRepository::read();
        $laws = LawLibraryRepository::numbered($this->snapshot['data']);
        if (!isset($laws[$this->lawKey])) throw new IllegalLinkException();
        $this->law = $laws[$this->lawKey];
    }

    public function assignVariables()
    {
        parent::assignVariables();
        $html = \nl2br(StringUtil::encodeHTML($this->law['content']));
        if (($this->law['format'] ?? 'plain') === 'html') {
            $processor = new LawHtmlOutputProcessor();
            $processor->process($this->law['content'], 'de.eulenthal.wcf.lawLibrary.message', 0);
            $processor->applyListStyle($this->law['listStyle'] ?? 'default');
            $html = $processor->getHtml();
        }
        LawLibraryRepository::assignSidebar($this->snapshot['data'], $this->law['categoryId'], $this->lawKey);
        WCF::getTPL()->assign(['law' => $this->law, 'lawHtml' => $html, 'lawIsBookmarked' => \in_array($this->lawKey, LawLibraryRepository::bookmarks(), true)]);
    }
}
