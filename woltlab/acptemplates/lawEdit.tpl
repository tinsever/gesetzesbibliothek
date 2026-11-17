{include file='header' pageTitle='wcf.lawLibrary.title'}
<header class="contentHeader">
    <div class="contentHeaderTitle"><h1 class="contentTitle">{if $editedLaw}{lang}wcf.lawLibrary.edit{/lang}{else}{lang}wcf.lawLibrary.add{/lang}{/if}</h1></div>
    <nav class="contentHeaderNavigation"><ul>{if $editedLaw}<li><a class="button" href="{link controller='LawFormatting' lawKey=$editedLaw[id]}{/link}">{icon name='list'} <span>{lang}wcf.lawLibrary.formatting{/lang}</span></a></li>{/if}<li><a class="button" href="{link controller='LawList' categoryId=$selectedCategory}{/link}">{icon name='list'} <span>{lang}wcf.lawLibrary.all{/lang}</span></a></li></ul></nav>
</header>
<p class="info">{lang}wcf.lawLibrary.numbering.description{/lang}</p>
{unsafe:$form->getHtml()}
{if $editedLaw}
<section class="section" id="amendmentHistory">
    <h2 class="sectionTitle">{lang}wcf.lawLibrary.history{/lang}</h2>
    {if $editedLaw[amendments]|count}
        <div class="tabularBox"><table class="table">
            <thead><tr><th class="columnDate">{lang}wcf.lawLibrary.amendment.date{/lang}</th><th class="columnText">{lang}wcf.lawLibrary.amendment.law{/lang}</th><th class="columnIcon"><span class="visuallyHidden">{lang}wcf.global.button.edit{/lang}</span></th></tr></thead>
            <tbody>{foreach from=$editedLaw[amendments] item=amendment}<tr>
                <td class="columnDate">{$amendment[dateFormatted]}</td>
                <td class="columnText"><a href="{link controller='Law' isACP=false lawKey=$amendment[lawKey]}{/link}">{$amendment[number]} · {$amendment[title]}</a></td>
                <td class="columnIcon"><a href="{link controller='LawAmendmentEdit' lawKey=$editedLaw[id] amendmentId=$amendment[id]}{/link}" aria-label="{lang}wcf.lawLibrary.amendment.edit{/lang}">{icon name='pencil'}</a></td>
            </tr>{/foreach}</tbody>
        </table></div>
    {else}<p class="info">{lang}wcf.lawLibrary.history.empty{/lang}</p>{/if}
    <p><a class="button" href="{link controller='LawAmendmentEdit' lawKey=$editedLaw[id]}{/link}">{icon name='plus'} <span>{lang}wcf.lawLibrary.amendment.add{/lang}</span></a></p>
</section>
{/if}
{include file='footer'}
