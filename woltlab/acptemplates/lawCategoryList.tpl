{include file='header' pageTitle='wcf.lawLibrary.systematic'}
<header class="contentHeader">
    <div class="contentHeaderTitle"><h1 class="contentTitle">{lang}wcf.lawLibrary.systematic{/lang}</h1></div>
    <nav class="contentHeaderNavigation"><ul>
        <li><a class="button" href="{link controller='LawList'}{/link}">{icon name='list'} <span>{lang}wcf.lawLibrary.all{/lang}</span></a></li>
        <li><a class="button buttonPrimary" href="{link controller='LawCategoryEdit'}{/link}">{icon name='plus'} <span>{lang}wcf.lawLibrary.category.add{/lang}</span></a></li>
    </ul></nav>
</header>
{if $saved}<p class="success">{lang}wcf.global.success.edit{/lang}</p>{/if}
<div class="section tabularBox">
    <table class="table">
        <thead><tr><th class="columnIcon"></th><th class="columnText">{lang}wcf.lawLibrary.category.name{/lang}</th><th class="columnText">{lang}wcf.lawLibrary.category.range{/lang}</th><th class="columnDigits">{lang}wcf.lawLibrary.category.lawCount{/lang}</th></tr></thead>
        <tbody>{foreach from=$lawCategories item=category}<tr>
            <td class="columnIcon"><a href="{link controller='LawCategoryEdit' categoryId=$category[id]}{/link}" title="{lang}wcf.global.button.edit{/lang}" aria-label="{lang}wcf.global.button.edit{/lang}">{icon name='pencil'}</a></td>
            <td class="columnText"><a href="{link controller='LawCategoryEdit' categoryId=$category[id]}{/link}">{$category[title]}</a>{if $category[description]}<p class="small">{$category[description]}</p>{/if}</td>
            <td class="columnText">{$category[range]}</td>
            <td class="columnDigits"><a href="{link controller='LawList' categoryId=$category[id]}{/link}">{#$category[count]}</a></td>
        </tr>{/foreach}</tbody>
    </table>
</div>
{include file='footer'}
