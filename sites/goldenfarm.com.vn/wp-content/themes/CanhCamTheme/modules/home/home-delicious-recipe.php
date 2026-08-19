<?php
$home_delicious_recipe_title = get_field('home_delicious_recipe_title', get_the_ID());
$home_choose_delicious_recipe = get_field('home_choose_delicious_recipe', get_the_ID());
$home_choose_news_delicious_recipe = get_field('home_choose_news_delicious_recipe', get_the_ID());
?>

<section class="home-delicious-recipe section-t overflow-hidden">
    <div class="container">
        <h2 class="site-title text-center">
            <?php echo esc_html($home_delicious_recipe_title); ?>
        </h2>
        <?php if ($home_choose_delicious_recipe) : ?>
            <!-- ĐÃ SỬA: Thêm class recipe-nav-scroll để xử lý cuộn mượt trên Mobile -->
            <nav class="site-nav recipe-nav-scroll mt-8">
                <ul class="recipe-nav-list">
                    <?php foreach ($home_choose_delicious_recipe as $item) : ?>
                        <li>
                            <a href="<?php echo esc_url(get_term_link($item->term_id)); ?>">
                                <?php echo esc_html($item->name); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <?php if ($home_choose_news_delicious_recipe) : ?>
            <div class="swiper-relative home-delicious-recipe-slider four-slider pb-15 mt-10 is-linear lg:pb-0 lg:mt-16">
                <div class="swiper">
                    <div class="swiper-wrapper">
                        <?php foreach ($home_choose_news_delicious_recipe as $item) : ?>
                            <div class="swiper-slide">
                                <?php get_template_part('modules/news/delicious_recipe_item', '', array('idPost' => $item->ID, 'showCategory' => true)); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mobile-only">
                    <div class="swiper-pagination"></div>
                </div>
                <div class="desktop-only">
                    <div class="swiper-button is-abs is-top-35">
                        <div class="button-prev"><i class="fa-thin fa-chevron-left"></i></div>
                        <div class="button-next"><i class="fa-thin fa-chevron-right"></i></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
