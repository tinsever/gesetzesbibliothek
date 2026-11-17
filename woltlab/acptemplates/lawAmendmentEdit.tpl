{include file='header' pageTitle='wcf.lawLibrary.history'}
<header class="contentHeader">
    <div class="contentHeaderTitle"><h1 class="contentTitle">{if $editedAmendment}{lang}wcf.lawLibrary.amendment.edit{/lang}{else}{lang}wcf.lawLibrary.amendment.add{/lang}{/if}</h1><p class="contentHeaderDescription">{$editedLaw[number]} · {$editedLaw[title]}</p></div>
    <nav class="contentHeaderNavigation"><ul><li><a class="button" href="{link controller='LawEdit' lawKey=$editedLaw[id]}{/link}">{icon name='arrow-left'} <span>{lang}wcf.lawLibrary.edit{/lang}</span></a></li></ul></nav>
</header>
<p class="info">{lang}wcf.lawLibrary.amendment.description{/lang}</p>
{unsafe:$form->getHtml()}
{include file='footer'}
