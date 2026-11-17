<?php
require __DIR__ . '/../woltlab/files/lib/system/lawLibrary/LawLibrary.class.php';
use wcf\system\lawLibrary\LawLibrary;
function check(bool $value, string $message): void {
    if (!$value) throw new RuntimeException($message);
    echo "PASS: $message\n";
}
function rejects(callable $fn, string $message): void {
    try { $fn(); } catch (InvalidArgumentException) { check(true, $message); return; }
    throw new RuntimeException($message);
}
$base = ['id'=>'a', 'categoryId'=>'public', 'title'=>'Gesetz', 'abbreviation'=>'G', 'date'=>'2026-09-09', 'status'=>'active', 'content'=>"§ 1\nText", 'updated'=>'2026-09-01'];
$data = ['version'=>1, 'categories'=>[['id'=>'public','base'=>100],['id'=>'civil','base'=>200]], 'laws'=>[$base,array_replace($base,['id'=>'b']),array_replace($base,['id'=>'c','categoryId'=>'civil'])]];
$save = ['action'=>'save','law'=>array_replace($base,['id'=>'new']),'beforeId'=>'b'];
$result = LawLibrary::apply($data,$save,'2026-09-09');
check(array_column($result['laws'],'id') === ['a','new','b','c'], 'insert before existing law');
check($result['laws'][1]['updated'] === '2026-09-01', 'manual modification date is preserved');
check(array_column($data['laws'],'id') === ['a','b','c'], 'mutation does not change input');
$result = LawLibrary::apply($data,['action'=>'move','id'=>'b','direction'=>-1],'2026-09-09');
check(array_column($result['laws'],'id') === ['b','a','c'], 'move only within category');
$result = LawLibrary::apply($data,['action'=>'save','law'=>array_replace($base,['categoryId'=>'civil']),'beforeId'=>'c'],'2026-09-09');
check(array_column($result['laws'],'id') === ['b','a','c'] && $result['laws'][1]['categoryId']==='civil', 'move between categories preserves identity');
foreach (['id'=>'<script>', 'date'=>'2026-02-30', 'categoryId'=>'missing', 'status'=>'draft', 'title'=>' ', 'content'=>'', 'abbreviation'=>str_repeat('x',31)] as $key=>$value) {
    rejects(fn()=>LawLibrary::apply($data,['action'=>'save','law'=>array_replace($base,[$key=>$value])],'2026-09-09'), 'reject invalid '.$key);
}
rejects(fn()=>LawLibrary::apply($data,array_replace($save,['beforeId'=>'c']),'2026-09-09'),'reject insertion into another category');
rejects(fn()=>LawLibrary::apply($data,['action'=>'move','id'=>'a','direction'=>2],'2026-09-09'),'reject invalid direction');
rejects(fn()=>LawLibrary::apply($data,['action'=>'delete','id'=>'a'],'2026-09-09'),'reject unimplemented action');
$full=$data; $full['laws']=array_map(fn($i)=>array_replace($base,['id'=>'law-'.$i]),range(1,99));
rejects(fn()=>LawLibrary::apply($full,$save,'2026-09-09'),'reject category overflow');
$edited=LawLibrary::apply($full,['action'=>'save','law'=>$full['laws'][0],'beforeId'=>'law-2'],'2026-09-09');
check(count($edited['laws']) === 99, 'editing full category remains possible');

$categoryData = ['version'=>1, 'categories'=>[['id'=>'public','base'=>100,'title'=>'Öffentliches Recht'],['id'=>'civil','base'=>200,'title'=>'Privatrecht']], 'laws'=>[$base]];
$categoryCommand = ['action'=>'saveCategory','id'=>'new_category','title'=>'Staatsverträge','description'=>'Völkerrechtliche Verträge','base'=>1200,'beforeId'=>'civil'];
$changed=LawLibrary::apply($categoryData,$categoryCommand,'2026-09-09');
check(array_column($changed['categories'],'id')===['public','new_category','civil'],'insert a category between existing areas');
check($changed['laws']===$categoryData['laws'],'category insertion preserves all laws and IDs');
$changed=LawLibrary::apply($categoryData,array_replace($categoryCommand,['id'=>'public','title'=>'Staatsrecht']),'2026-09-09');
check($changed['categories'][0]['title']==='Staatsrecht' && $changed['categories'][0]['base']===1200,'rename and change occupied category number block');
check($changed['laws'][0]['categoryId']==='public','number block changes preserve law references');
$changed=LawLibrary::apply($categoryData,array_replace($categoryCommand,['id'=>'civil','base'=>200,'beforeId'=>'public']),'2026-09-09');
check(array_column($changed['categories'],'id')===['civil','public'] && $changed['categories'][1]['base']===100,'reorder categories without renumbering unrelated areas');
foreach (['base'=>200,'title'=>'  ','description'=>str_repeat('x',2001),'id'=>'<script>','beforeId'=>'missing'] as $key=>$value) {
    rejects(fn()=>LawLibrary::apply($categoryData,array_replace($categoryCommand,[$key=>$value]),'2026-09-09'),'reject invalid category '.$key);
}
foreach ([0,-100,101,1000000,'300',300.5] as $invalidBase) {
    rejects(fn()=>LawLibrary::apply($categoryData,array_replace($categoryCommand,['base'=>$invalidBase]),'2026-09-09'),'reject invalid number block '.json_encode($invalidBase));
}
rejects(fn()=>LawLibrary::apply($categoryData,array_replace($categoryCommand,['beforeId'=>'new_category']),'2026-09-09'),'reject self-referencing category position');
rejects(fn()=>LawLibrary::apply($categoryData,['action'=>'deleteCategory','id'=>'public'],'2026-09-09'),'prevent deleting a populated category');
$changed=LawLibrary::apply($categoryData,['action'=>'deleteCategory','id'=>'civil'],'2026-09-09');
check(count($changed['categories'])===1 && $changed['laws']===$categoryData['laws'],'delete an empty category without affecting laws');
$changed['laws']=[];
rejects(fn()=>LawLibrary::apply($changed,['action'=>'deleteCategory','id'=>'public'],'2026-09-09'),'keep at least one category');
rejects(fn()=>LawLibrary::apply($categoryData,['action'=>'deleteCategory','id'=>'missing'],'2026-09-09'),'reject deletion of missing area');
$many=$categoryData;$many['categories']=array_map(fn($n)=>['id'=>'area'.$n,'base'=>$n*100,'title'=>'Area'],range(1,99));
rejects(fn()=>LawLibrary::apply($many,array_replace($categoryCommand,['base'=>10000,'beforeId'=>'']),'2026-09-09'),'reject category overflow');

// Upgrade compatibility: optional metadata is never required on old records.
$legacy = $data;
$legacy['laws'][0]['futureMetadata'] = ['keep' => 'ä ö ü'];
$input = $base; unset($input['updated']);
$changed = LawLibrary::apply($legacy, ['action'=>'save','law'=>$input,'beforeId'=>'b'], '2026-09-11');
check($changed['laws'][0]['updated']==='2026-09-01', 'ordinary edits preserve legacy modification date');
check($changed['laws'][0]['futureMetadata']===$legacy['laws'][0]['futureMetadata'], 'editing preserves unrecognized existing metadata');
check(!isset($changed['laws'][0]['amendments']), 'legacy edit does not invent amendment history');
foreach (['2026-02-30','0000-01-01','2026-9-01',[],false,''] as $invalid) {
 rejects(fn()=>LawLibrary::apply($data,['action'=>'save','law'=>array_replace($base,['updated'=>$invalid])],'2026-09-11'), 'reject invalid updated '.json_encode($invalid));
}
$command=['action'=>'saveAmendment','lawKey'=>'a','id'=>'change1','date'=>'2025-01-20','amendingLawKey'=>'c'];
$history = LawLibrary::apply($legacy,$command,'2026-09-11');
check($history['laws'][0]['updated']==='2026-09-01', 'backdated history preserves later manual date');
$expected = $legacy; $expected['laws'][0]['amendments']=[['id'=>'change1','date'=>'2025-01-20','lawKey'=>'c']];
check($history===$expected, 'adding history preserves law content, format, order and all unrelated data exactly');
$history = LawLibrary::apply($history,array_replace($command,['id'=>'change2','date'=>'2026-09-10','amendingLawKey'=>'b']),'2026-09-11');
check($history['laws'][0]['updated']==='2026-09-10', 'newest amendment advances modification date');
$history = LawLibrary::apply($history,array_replace($command,['id'=>'change3','date'=>'2026-09-10','amendingLawKey'=>'c']),'2026-09-11');
check(count($history['laws'][0]['amendments'])===3, 'different amending laws on same date are supported');
$edited = LawLibrary::apply($history,['action'=>'save','law'=>array_replace($base,['updated'=>'2026-09-10','amendingLawKey'=>'c'])],'2026-09-11');
$a = array_values(array_filter($edited['laws'],fn($l)=>$l['id']==='a'))[0];
check($a['amendments']===$history['laws'][0]['amendments'], 'quick link avoids duplicates and law edits preserve full history');
foreach (['amendingLawKey'=>'a','date'=>'2026-02-30','lawKey'=>'missing','id'=>'<bad>'] as $field=>$value) {
 rejects(fn()=>LawLibrary::apply($history,array_replace($command,[$field=>$value]),'2026-09-11'), 'reject malformed amendment '.$field);
}
rejects(fn()=>LawLibrary::apply($history,array_replace($command,['amendingLawKey'=>'missing']),'2026-09-11'),'reject missing source law');
rejects(fn()=>LawLibrary::apply($history,array_replace($command,['id'=>'duplicate']),'2026-09-11'),'reject duplicate history entry');
rejects(fn()=>LawLibrary::apply($history,['action'=>'save','law'=>$base],'2026-09-11'),'reject manual date before history');
$corrected=LawLibrary::apply($history,array_replace($command,['date'=>'2024-02-29','amendingLawKey'=>'b']),'2026-09-11');
check($corrected['laws'][0]['amendments'][0]['date']==='2024-02-29','history date and source can be corrected');
$removed=LawLibrary::apply($corrected,['action'=>'deleteAmendment','lawKey'=>'a','id'=>'change1'],'2026-09-11');
check(count($removed['laws'])===3 && count($removed['laws'][0]['amendments'])===2 && $removed['laws'][0]['content']===$legacy['laws'][0]['content'],'removing a link preserves both laws and their text');
$renumbered=LawLibrary::apply($history,['action'=>'move','id'=>'b','direction'=>-1],'2026-09-11');
check($renumbered['laws'][1]['amendments'][1]['lawKey']==='b','reordering preserves history source keys');

$styled = LawLibrary::apply($history, ['action'=>'setListStyle','lawKey'=>'a','listStyle'=>'upper-alpha'], '2026-09-12');
$styleExpected = $history; $styleExpected['laws'][0]['listStyle'] = 'upper-alpha';
check($styled === $styleExpected, 'lettered list setting preserves every existing byte of law fields, history and dates');
$withoutStyle = $base; unset($withoutStyle['updated']);
$styleEdit = LawLibrary::apply($styled, ['action'=>'save','law'=>$withoutStyle], '2026-09-12');
$a = array_values(array_filter($styleEdit['laws'],fn($l)=>$l['id']==='a'))[0];
check($a['listStyle']==='upper-alpha', 'older forms cannot reset saved list formatting');
$reset = LawLibrary::apply($styled, ['action'=>'setListStyle','lawKey'=>'a','listStyle'=>'default'], '2026-09-12');
$styleExpected['laws'][0]['listStyle']='default';
check($reset===$styleExpected, 'reverting the display style preserves all original data');
foreach (['lower-alpha','<script>','',[],null] as $invalidStyle) {
 rejects(fn()=>LawLibrary::apply($history,['action'=>'setListStyle','lawKey'=>'a','listStyle'=>$invalidStyle],'2026-09-12'),'reject invalid list style '.json_encode($invalidStyle));
 rejects(fn()=>LawLibrary::apply($data,['action'=>'save','law'=>array_replace($base,['listStyle'=>$invalidStyle])],'2026-09-12'),'reject invalid list style in main editor '.json_encode($invalidStyle));
}
rejects(fn()=>LawLibrary::apply($history,['action'=>'setListStyle','lawKey'=>'missing','listStyle'=>'upper-alpha'],'2026-09-12'),'reject style change for nonexistent law');
