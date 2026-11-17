{capture assign='sidebarLeft'}
    <section class="box" data-static-box-identifier="de.eulenthal.wcf.lawLibrary.Systematic">
        <h2 class="boxTitle">{lang}wcf.lawLibrary.systematic{/lang}</h2>
        <nav class="boxContent" aria-label="{lang}wcf.lawLibrary.systematic{/lang}">
            <ol class="boxMenu">
                <li{if !$selectedCategory} class="active"{/if}>
                    <a class="boxMenuLink" href="{link controller='LawLibrary'}{/link}"><span class="boxMenuLinkTitle">{lang}wcf.lawLibrary.all{/lang}</span></a>
                </li>
                {foreach from=$lawCategories item=category}
                    <li class="boxMenuHasChildren{if $selectedCategory == $category[id]} active{/if}">
                        <a class="boxMenuLink" href="{link controller='LawLibrary' categoryId=$category[id]}{/link}">
                            <span class="boxMenuLinkTitle">{$category[prefix]} {$category[title]}</span>
                            <span class="badge">{#$category[laws]|count}</span>
                        </a>
                        {if $selectedCategory == $category[id] && $category[laws]|count}
                            <ol class="boxMenuDepth1">
                                {foreach from=$category[laws] item=entry}
                                    <li{if $selectedLawKey == $entry[id]} class="active"{/if}>
                                        <a class="boxMenuLink" href="{link controller='Law' lawKey=$entry[id]}{/link}"{if $selectedLawKey == $entry[id]} aria-current="page"{/if}>
                                            <span class="boxMenuLinkTitle">{$entry[number]} {$entry[title]}</span>
                                        </a>
                                    </li>
                                {/foreach}
                            </ol>
                        {/if}
                    </li>
                {/foreach}
            </ol>
        </nav>
    </section>
{/capture}
