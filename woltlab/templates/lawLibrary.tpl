{capture assign='pageTitle'}{$lawLibraryName}{/capture}
{capture assign='contentTitle'}{$lawLibraryName}{/capture}
{capture assign='contentDescription'}{$lawCategoryDescription}{/capture}
{capture assign='contentHeaderNavigation'}
    {if $canManageLaws}<li><a href="{link controller='LawList' isACP=true}{/link}" class="button">{icon name='gear'} <span>{lang}wcf.lawLibrary.manage{/lang}</span></a></li><li><a href="{link controller='LawEdit' isACP=true categoryId=$selectedCategory}{/link}" class="button buttonPrimary">{icon name='plus'} <span>{lang}wcf.lawLibrary.add{/lang}</span></a></li>{/if}
{/capture}
{include file='lawLibrarySidebar'}
{include file='header'}
<nav class="tabMenu">
    <ul>
        <li{if $lawView == 'all'} class="active"{/if}><a href="{link controller='LawLibrary' categoryId=$selectedCategory}{/link}">{lang}wcf.lawLibrary.all{/lang}</a></li>
        <li{if $lawView == 'recent'} class="active"{/if}><a href="{link controller='LawLibrary' view='recent'}{/link}">{lang}wcf.lawLibrary.recent{/lang}</a></li>
        {if $__wcf->user->userID}<li{if $lawView == 'bookmarks'} class="active"{/if}><a href="{link controller='LawLibrary' view='bookmarks'}{/link}">{lang}wcf.lawLibrary.bookmarks{/lang}</a></li>{/if}
    </ul>
</nav>
<section class="section">
    <form method="post" action="{link controller='LawLibrary'}{/link}">
        <input type="hidden" name="categoryId" value="{$selectedCategory}">
        <input type="hidden" name="view" value="{$lawView}">
        <dl>
            <dt><label for="lawQuery">{lang}wcf.lawLibrary.search{/lang}</label></dt>
            <dd><input type="search" id="lawQuery" name="query" value="{$query}" class="long" maxlength="250" placeholder="{lang}wcf.lawLibrary.search.placeholder{/lang}"></dd>
        </dl>
        <dl>
            <dt><label for="lawStatus">{lang}wcf.lawLibrary.status{/lang}</label></dt>
            <dd><select id="lawStatus" name="status">
                <option value="">{lang}wcf.lawLibrary.status.all{/lang}</option>
                <option value="active"{if $lawStatus == 'active'} selected{/if}>{lang}wcf.lawLibrary.status.active{/lang}</option>
                <option value="announced"{if $lawStatus == 'announced'} selected{/if}>{lang}wcf.lawLibrary.status.announced{/lang}</option>
                <option value="repealed"{if $lawStatus == 'repealed'} selected{/if}>{lang}wcf.lawLibrary.status.repealed{/lang}</option>
            </select></dd>
        </dl>
        <div class="formSubmit"><button type="submit" class="button buttonPrimary">{lang}wcf.lawLibrary.search{/lang}</button></div>
    </form>
</section>
<section class="section">
    <h2 class="sectionTitle">{if $query}{lang}wcf.lawLibrary.results{/lang}{elseif $lawView == 'bookmarks'}{lang}wcf.lawLibrary.bookmarks{/lang}{elseif $lawView == 'recent'}{lang}wcf.lawLibrary.recent{/lang}{elseif $lawCategoryTitle}{$lawCategoryTitle}{else}{lang}wcf.lawLibrary.all{/lang}{/if} <span class="badge">{#$laws|count}</span></h2>
    {if $laws|count}
        <div class="tabularBox">
            <table class="table">
                <thead><tr><th class="columnID">{lang}wcf.lawLibrary.number{/lang}</th><th class="columnText">{lang}wcf.lawLibrary.lawTitle{/lang}</th><th class="columnText">{lang}wcf.lawLibrary.status{/lang}</th><th class="columnDate">{lang}wcf.lawLibrary.date{/lang}</th><th class="columnDate">{lang}wcf.lawLibrary.updated{/lang}</th>{if $canManageLaws}<th class="columnIcon"><span class="visuallyHidden">{lang}wcf.global.button.edit{/lang}</span></th>{/if}</tr></thead>
                <tbody>
                    {foreach from=$laws item=law}
                        <tr>
                            <td class="columnID">{$law[number]}</td>
                            <td class="columnText"><a href="{link controller='Law' lawKey=$law[id]}{/link}">{$law[title]}</a>{if $law[abbreviation]}<br><small>{$law[abbreviation]}</small>{/if}</td>
                            <td class="columnText"><span class="badge{if $law[status] == 'active'} green{elseif $law[status] == 'announced'} yellow{else} red{/if}">{lang}wcf.lawLibrary.status.{$law[status]}{/lang}</span></td>
                            <td class="columnDate">{$law[dateFormatted]}</td><td class="columnDate">{$law[updatedFormatted]}</td>
                            {if $canManageLaws}<td class="columnIcon"><a href="{link controller='LawEdit' isACP=true lawKey=$law[id]}{/link}" title="{lang}wcf.global.button.edit{/lang}" aria-label="{lang}wcf.global.button.edit{/lang}">{icon name='pencil'}</a></td>{/if}
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    {else}<p class="info">{lang}wcf.lawLibrary.empty{/lang}</p>{/if}
</section>
{include file='footer'}
