{include file='header' pageTitle='wcf.lawLibrary.formatting'}
<header class="contentHeader">
    <div class="contentHeaderTitle"><h1 class="contentTitle">{lang}wcf.lawLibrary.formatting{/lang}</h1><p class="contentHeaderDescription">{$editedLaw[title]}</p></div>
    <nav class="contentHeaderNavigation"><ul><li><a class="button" href="{link controller='Law' isACP=false lawKey=$editedLaw[id]}{/link}">{icon name='arrow-left'} <span>{lang}wcf.lawLibrary.formatting.back{/lang}</span></a></li></ul></nav>
</header>
<p class="info">{lang}wcf.lawLibrary.formatting.description{/lang}</p>
{unsafe:$form->getHtml()}
{include file='footer'}
