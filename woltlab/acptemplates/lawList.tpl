{include file='header' pageTitle='wcf.lawLibrary.title'}
<header class="contentHeader">
    <div class="contentHeaderTitle"><h1 class="contentTitle">{lang}wcf.lawLibrary.title{/lang}</h1></div>
    <nav class="contentHeaderNavigation"><ul>
        <li><a class="button" href="{link controller='LawLibrary' forceFrontend=true}{/link}">{icon name='arrow-up-right-from-square'} <span>{lang}wcf.lawLibrary.showLibrary{/lang}</span></a></li>
        <li><a class="button" href="{link controller='LawCategoryList'}{/link}">{icon name='list'} <span>{lang}wcf.lawLibrary.systematic{/lang}</span></a></li>
        <li><a class="button buttonPrimary" href="{link controller='LawEdit' categoryId=$selectedCategory}{/link}">{icon name='plus'} <span>{lang}wcf.lawLibrary.add{/lang}</span></a></li>
    </ul></nav>
</header>
{if $saved}<p class="success">{lang}wcf.global.success.edit{/lang}</p>{/if}
{if $laws|count}
<div class="section tabularBox"><table class="table">
    <thead><tr><th class="columnIcon"></th><th class="columnID">{lang}wcf.lawLibrary.number{/lang}</th><th class="columnText">{lang}wcf.lawLibrary.lawTitle{/lang}</th><th class="columnText">{lang}wcf.lawLibrary.category{/lang}</th><th class="columnText">{lang}wcf.lawLibrary.status{/lang}</th></tr></thead>
    <tbody>{foreach from=$laws item=law}<tr>
        <td class="columnIcon"><a href="{link controller='LawEdit' lawKey=$law[id]}{/link}" title="{lang}wcf.global.button.edit{/lang}" aria-label="{lang}wcf.global.button.edit{/lang}">{icon name='pencil'}</a></td>
        <td class="columnID">{$law[number]}</td>
        <td class="columnText"><a href="{link controller='LawEdit' lawKey=$law[id]}{/link}">{$law[title]}</a>{if $law[abbreviation]}<p class="small">{$law[abbreviation]}</p>{/if}</td>
        <td class="columnText">{$law[categoryTitle]}</td>
        <td class="columnText">{lang}wcf.lawLibrary.status.{$law[status]}{/lang}</td>
    </tr>{/foreach}</tbody>
</table></div>
{else}<p class="info">{lang}wcf.lawLibrary.empty{/lang}</p>{/if}
{include file='footer'}
