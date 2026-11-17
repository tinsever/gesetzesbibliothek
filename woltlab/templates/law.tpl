{capture assign='pageTitle'}{$law[number]} – {$law[title]}{/capture}
{capture assign='contentTitle'}{$law[title]}{/capture}
{capture assign='contentHeaderNavigation'}
    <li><a class="button" href="{link controller='LawLibrary' categoryId=$law[categoryId]}{/link}">{icon name='arrow-left'} <span>{lang}wcf.lawLibrary.back{/lang}</span></a></li>
    {if $canManageLaws}<li><a class="button" href="{link controller='LawFormatting' isACP=true lawKey=$law[id]}{/link}">{icon name='list'} <span>{lang}wcf.lawLibrary.formatting{/lang}</span></a></li><li><a class="button buttonPrimary" href="{link controller='LawEdit' isACP=true lawKey=$law[id]}{/link}">{icon name='pencil'} <span>{lang}wcf.global.button.edit{/lang}</span></a></li>{/if}
{/capture}
{include file='lawLibrarySidebar'}
{include file='header'}
<section class="section">
    <dl class="plain dataList">
        <dt>{lang}wcf.lawLibrary.number{/lang}</dt><dd>{$law[number]}</dd>
        <dt>{lang}wcf.lawLibrary.status{/lang}</dt><dd>{lang}wcf.lawLibrary.status.{$law[status]}{/lang}</dd>
        <dt>{lang}wcf.lawLibrary.date{/lang}</dt><dd>{$law[dateFormatted]}</dd>
        <dt>{lang}wcf.lawLibrary.updated{/lang}</dt><dd>{$law[updatedFormatted]}{foreach from=$law[latestAmendments] item=amendment}<br><a href="{link controller='Law' lawKey=$amendment[lawKey]}{/link}">{$amendment[number]} · {$amendment[title]}</a>{/foreach}</dd>
    </dl>
</section>
<article class="section htmlContent">{unsafe:$lawHtml}</article>
<section class="section" id="amendmentHistory">
    <h2 class="sectionTitle">{lang}wcf.lawLibrary.history{/lang}</h2>
    {if $law[amendments]|count}
        <div class="tabularBox"><table class="table">
            <thead><tr><th class="columnDate">{lang}wcf.lawLibrary.amendment.date{/lang}</th><th class="columnText">{lang}wcf.lawLibrary.amendment.law{/lang}</th>{if $canManageLaws}<th class="columnIcon"><span class="visuallyHidden">{lang}wcf.global.button.edit{/lang}</span></th>{/if}</tr></thead>
            <tbody>{foreach from=$law[amendments] item=amendment}<tr>
                <td class="columnDate">{$amendment[dateFormatted]}</td>
                <td class="columnText"><a href="{link controller='Law' lawKey=$amendment[lawKey]}{/link}">{$amendment[number]} · {$amendment[title]}</a></td>
                {if $canManageLaws}<td class="columnIcon"><a href="{link controller='LawAmendmentEdit' isACP=true lawKey=$law[id] amendmentId=$amendment[id]}{/link}" aria-label="{lang}wcf.lawLibrary.amendment.edit{/lang}">{icon name='pencil'}</a></td>{/if}
            </tr>{/foreach}</tbody>
        </table></div>
    {else}<p class="info">{lang}wcf.lawLibrary.history.empty{/lang}</p>{/if}
    {if $canManageLaws}<p><a class="button" href="{link controller='LawAmendmentEdit' isACP=true lawKey=$law[id]}{/link}">{icon name='plus'} <span>{lang}wcf.lawLibrary.amendment.add{/lang}</span></a></p>{/if}
</section>

{if $__wcf->user->userID}
    <form method="post" action="{link controller='LawBookmark'}{/link}" class="section">
        <input type="hidden" name="lawKey" value="{$law[id]}">
        <input type="hidden" name="saved" value="{if $lawIsBookmarked}0{else}1{/if}">
        {csrfToken}
        <button class="button" type="submit">{icon name='bookmark'} <span>{if $lawIsBookmarked}{lang}wcf.lawLibrary.bookmark.remove{/lang}{else}{lang}wcf.lawLibrary.bookmark.add{/lang}{/if}</span></button>
    </form>
{/if}
{include file='footer'}
