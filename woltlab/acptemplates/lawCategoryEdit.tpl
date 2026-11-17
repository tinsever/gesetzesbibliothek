{include file='header' pageTitle='wcf.lawLibrary.systematic'}
<header class="contentHeader">
    <div class="contentHeaderTitle"><h1 class="contentTitle">{if $editedCategory}{lang}wcf.lawLibrary.category.edit{/lang}{else}{lang}wcf.lawLibrary.category.add{/lang}{/if}</h1></div>
    <nav class="contentHeaderNavigation"><ul><li><a class="button" href="{link controller='LawCategoryList'}{/link}">{icon name='list'} <span>{lang}wcf.lawLibrary.systematic{/lang}</span></a></li></ul></nav>
</header>
{unsafe:$form->getHtml()}
{include file='footer'}
