<?php
$home_brand_list = get_field('home_brand_list', get_the_ID());
$home_video_intro = get_field('home_video_intro', get_the_ID());
if ($home_brand_list || $home_video_intro) :
?>
	<section class="home-brands section">
		<div class="container">
			<?php if ($home_brand_list) : ?>
			<div class="brands-list grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
				<?php foreach ($home_brand_list as $key => $item) : ?>
					<?php
					// Handle raw term IDs (ACF value may be plain array of IDs when field is not registered)
					if (is_numeric($item)) {
						$item = get_term(intval($item));
					}
					if (is_wp_error($item) || empty($item->term_id)) {
						continue;
					}
					// get product category thumbnail
					$category_thumbnail_url = wp_get_attachment_url(get_term_meta($item->term_id, 'thumbnail_id', true));
					$category_url = get_term_link($item->term_id);
					$choose_category_color = get_field('choose_category_color', 'product_cat_' . $item->term_id);
					$custom_url = get_field('custom_url', 'product_cat_' . $item->term_id);
					?>
					<a class="home-brands-item" href="<?php echo $custom_url ? $custom_url : $category_url ?>" style="color: <?php echo $choose_category_color ?>">
						<span class="logo img-contain">
							<img src="<?php echo $category_thumbnail_url ?>" alt="<?php echo $item->title ?>">
						</span>
						<span class="caption">
							<span>
								<?php echo $item->description ?>
							</span>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<?php if ($home_brand_list) : ?>
			<div class="dealer-search mt-8">
				<form method="GET" role="form" aria-label="<?php echo _e('Tìm kiếm', 'canhcamtheme') ?>" novalidate="novalidate" action="<?php bloginfo('url') ?>/">
					<div class="input-group">
						<input type="text" placeholder="<?php echo _e('Bạn muốn tìm gì?', 'canhcamtheme') ?>" name="s">
						<div class="button-group">
							<button class="btn-solid btn-submit" type="submit">
								<i class="fa-light fa-search"></i>
								<span><?php echo _e('Tìm kiếm', 'canhcamtheme') ?></span>
							</button>
						</div>
					</div>
				</form>
			</div>
			<?php endif; ?>
			<?php if ($home_video_intro) : ?>
				<div class="brands-video mt-10">
					<div class="iframe-scale">
						<div id="play-button">
							<i class="fa-solid fa-play"></i>
						</div>
						<div id="player"></div>
						<?php // echo $home_video_intro
						?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<script>
    // 2. This code loads the IFrame Player API code asynchronously.
    var playButton = document.getElementById("play-button");
    var tag = document.createElement('script');

    tag.src = "https://www.youtube.com/iframe_api";
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

    var player;

    // 3. This function creates an <iframe> (and YouTube player)
    //    after the API code downloads.
    function onYouTubeIframeAPIReady() {
        player = new YT.Player('player', {
            height: '390',
            width: '640',
            videoId: '<?php echo $home_video_intro ?>', // Thay bằng ID video của bạn
            playerVars: {
                'playsinline': 1,
                'mute': 1 // Mute video when it loads
            },
            events: {
                'onReady': onPlayerReady,
                'onStateChange': onPlayerStateChange
            }
        });
    }

    // This function is called when the video player is ready
    function onPlayerReady(event) {
        // Bind click event to playButton to play video and unmute it
        playButton.addEventListener("click", function() {
            player.unMute(); // Unmute the video
            player.playVideo(); // Play the video
            playButton.style.display = "none"; // Hide the play button
        });
    }

    // This function is called when the player's state changes
    function onPlayerStateChange(event) {
        if (event.data == YT.PlayerState.PLAYING) {
            // Hide the play button if video is playing
            playButton.style.display = "none";
        }
    }

    // Function to stop the video (if needed later)
    function stopVideo() {
        player.stopVideo();
    }
</script>
<?php endif; ?>
