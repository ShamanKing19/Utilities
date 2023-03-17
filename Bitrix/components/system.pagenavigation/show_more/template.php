<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

ob_start();
?>

<?php if($arResult["NavPageCount"] > 1):?>
    <?php if ($arResult["NavPageNomer"] + 1 <= $arResult["nEndPage"]):?>
        <?php
        $plus = $arResult["NavPageNomer"] + 1;
        $url = $arResult["sUrlPathParams"] . "PAGEN_" . $arResult["NavNum"] . "=" . $plus;
        ?>
        <div class="load-more">
            <button class="button button--icon button--show-more load-more__btn" type="button" data-url="<?=$url?>">
                <svg class="button__icon">
                    <use xlink:href="<?=MMB_FRONT_PATH;?>/img/sprite.svg#icon-arrow-circle"></use>
                </svg>
                <span class="button__text">Показать ещё</span>
            </button>
        </div>
    <?php endif?>
<?php endif?>

<script>
    $(document).on('click', '.load-more__btn', function() {
        let targetContainer = $('.show-more__container'),
            url = $('.load-more__btn').attr('data-url');

        if (url !== undefined) {
            $.ajax({
                type: 'GET',
                url: url,
                dataType: 'html',
                success: function(data) {
                    $('.load-more').remove();

                    let elements = $(data).find('.show-more__item'),
                        pagination = $(data).find('.load-more');

                    targetContainer.append(elements);
                    targetContainer.append(pagination);
                }
            });
        }
    });
</script>

<?php
$paging = ob_get_contents();
$paging = preg_replace_callback('/data-url="([^"]+)"/is', function($matches) {
    $url = $matches[1];
    $newUrl = '';
    if ($arUrl = parse_url($url)) {
        $newUrl .= $arUrl['path'];
        if (substr($newUrl, -1) != '/') {
            $newUrl .= '/';
        }

        $newUrl = preg_replace('#(page=[\d]+/)#is', '', $newUrl);
        parse_str(htmlspecialcharsback($arUrl['query']), $arQuery);
        foreach ($arQuery as $k => $v) {
            if (in_array($k, ['SECTION_CODE'])) {
                unset($arQuery[$k]);
            } elseif (substr($k, 0, 5) == 'PAGEN') {
                $arQuery['page'] = intval($v);
                unset($arQuery[$k]);
            }
        }

        $buildQuery = http_build_query(array_filter($arQuery));

        if (strlen($buildQuery)) {
            $newUrl .= '?' . $buildQuery;
        }
    }
    return 'data-url="'.$newUrl.'"';
}, $paging);

ob_end_clean();
echo $paging;