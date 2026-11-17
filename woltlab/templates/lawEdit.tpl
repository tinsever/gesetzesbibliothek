{capture assign='pageTitle'}{if $editedLaw}{lang}wcf.lawLibrary.edit{/lang}{else}{lang}wcf.lawLibrary.add{/lang}{/if}{/capture}
{capture assign='contentTitle'}{if $editedLaw}{lang}wcf.lawLibrary.edit{/lang}{else}{lang}wcf.lawLibrary.add{/lang}{/if}{/capture}
{capture assign='contentHeaderNavigation'}
    <li><a class="button" href="{link controller='LawLibrary' categoryId=$selectedCategory}{/link}">{icon name='arrow-left'} <span>{lang}wcf.lawLibrary.back{/lang}</span></a></li>
{/capture}
{include file='lawLibrarySidebar'}
{include file='header'}
<p class="info">{lang}wcf.lawLibrary.numbering.description{/lang}</p>
{unsafe:$form->getHtml()}
{include file='footer'}
