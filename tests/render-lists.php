<?php
// Run against an installed WoltLab test runtime. Does not write law records.
$runtime = getenv('LAW_WCF_DIR') ?: __DIR__ . '/../.local/woltlab/upload';
$_SERVER['HTTP_HOST']='127.0.0.1:8447';
$_SERVER['REQUEST_URI']='/index.php?law-library/';
$_SERVER['QUERY_STRING']='law-library/';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTPS']='on';
require $runtime . '/global.php';
require_once __DIR__.'/../woltlab/files/lib/system/lawLibrary/LawHtmlOutputProcessor.class.php';
use wcf\system\lawLibrary\LawHtmlOutputProcessor;
use wcf\system\html\input\HtmlInputProcessor;

function verify(bool $value, string $message): void {
 if (!$value) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
 echo "PASS: $message\n";
}
$input='<h2>Abschnitt eins</h2><ol style="list-style-type:upper-alpha"><li>Äußerer Punkt A<ol><li>Unterpunkt 1</li><li>Unterpunkt 2</li></ol></li><li>B</li><li>C</li><li>D</li><li>E</li><li>F</li><li>Verweis auf F.</li></ol><h2>Abschnitt zwei</h2><p>Ä, Ö, Ü und &amp; bleiben erhalten.</p><ol><li>Erneut A</li><li>B</li></ol><ul><li>Aufzählung<ol><li>Ziffer innerhalb der Aufzählung</li></ol></li></ul><h2>Abschnitt drei</h2><p>Schluss.</p>';
$sanitizer=new HtmlInputProcessor();
$sanitizer->process($input, 'de.eulenthal.wcf.lawLibrary.message', 0);
$stored=$sanitizer->getHtml();
verify(!str_contains($stored,'upper-alpha'), 'native WoltLab input strips the source list style (original failure reproduced)');
$before=hash('sha256',$stored);
$processor=new LawHtmlOutputProcessor();
$processor->enableToc=true;
$processor->process($stored,'de.eulenthal.wcf.lawLibrary.message',0);
$processor->applyListStyle('upper-alpha');
$html=$processor->getHtml();
$doc=new DOMDocument();@$doc->loadHTML('<?xml encoding="UTF-8">'.$html);
$x=new DOMXPath($doc);
verify($x->query('//ol[@type="A" and @style="list-style-type: upper-alpha"]')->length===2,'only top-level law lists receive capital letters');
verify($x->query('//ol/descendant::ol[@type="A"]')->length===0,'nested ordered lists keep their numbers');
verify($x->query('//ul/descendant::ol[@type="A"]')->length===0,'ordered lists inside bullet lists keep their numbers');
verify($x->query('//ol[contains(@class,"tableOfContents")]')->length>0,'native table of contents is still generated');
verify($x->query('//ol[contains(@class,"tableOfContents") and @type="A"]')->length===0,'table of contents retains its own numbering');
verify(str_contains($html,'Verweis auf F.') && str_contains($html,'Ä, Ö, Ü'),'text, cross references and umlauts survive rendering');
verify(hash('sha256',$stored)===$before,'formatting does not modify stored source HTML');
$plain=new LawHtmlOutputProcessor();$plain->process($stored,'de.eulenthal.wcf.lawLibrary.message',0);$baseline=$plain->getHtml();
$default=new LawHtmlOutputProcessor();$default->process($stored,'de.eulenthal.wcf.lawLibrary.message',0);$default->applyListStyle('default');
verify($default->getHtml()===$baseline,'default mode produces byte-identical output');
file_put_contents(__DIR__.'/../.local/list-style-preview.html','<!doctype html><meta charset="utf-8"><title>Listenformatierung – Prüfbeispiel</title><style>body{max-width:800px;margin:40px auto;font:18px system-ui}.tableOfContentsContainer{border:1px solid #aaa;padding:20px}ol{list-style-type:decimal}li{margin:8px 0}</style>'.$html);

require_once __DIR__.'/../woltlab/files/lib/acp/form/LawFormattingForm.class.php';
foreach (simplexml_load_file(__DIR__.'/../woltlab/language/de.xml')->xpath('//*[local-name()="item"]') as $item) {
    \wcf\system\WCF::getLanguage()->overrideItem((string)$item['name'], (string)$item);
}
$reflection = new ReflectionClass(\wcf\acp\form\LawFormattingForm::class);
$form = $reflection->newInstanceWithoutConstructor();
$reflection->getProperty('snapshot')->setValue($form, ['revision'=>23]);
$reflection->getProperty('law')->setValue($form, ['id'=>'test','title'=>'Testgesetz','listStyle'=>'upper-alpha']);
$form->buildForm();
$reflection->getMethod('setFormAction')->invoke($form);
$html = $form->form->getHtml();
verify(str_contains($html,'value="upper-alpha"') && str_contains($html,'Großbuchstaben'), 'native FormBuilder renders translated alphabetic selection');
verify($form->form->getNodeById('listStyle')->getValue()==='upper-alpha', 'native form preselects stored style');
verify($form->form->getNodeById('revision')->getValue()==='23', 'native form carries snapshot revision');
verify(!str_contains($html,'textarea'), 'format-only form contains no text editor');
