<?php
$GetDealerSearch = isset($_GET['DealerSearch']) ? $_GET['DealerSearch'] : '';
$GetGroupProduct = isset($_GET['GroupProduct']) ? $_GET['GroupProduct'] : '';

$dealerID = get_the_ID(); // get ID page

// Get info page by ID
$full_url = get_permalink($dealerID); // get full url
$image_url = get_the_post_thumbnail_url($dealerID, 'full'); // get image url

// ACF
$dealer_product_groups = get_field('dealer_product_groups', $dealerID);
?>
<?php if ($GetDealerSearch) : ?>
	<div class="box-dealer-detail mx-auto mt-8">
		<?php
		// Shops - Custom Post Type
		$shop_args = array(
			'post_type' => 'shop',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			// 'meta_query' => array(
			// 	array(
			// 		'key'        => 'shop_info',
			// 		'compare'    => '=',
			// 		// 'value'      => 1
			// 	)
			// ),
			// 'meta_key' => 'zone',
			'orderby' => 'meta_value_num',
			'order' => 'DESC',
			'meta_query' => array(),
		);
		$shop_query = new WP_Query($shop_args);
		// log_dump($shop_query);

		if ($shop_query->have_posts()) :
		?>
			<div class="dealer-search">
				<form action="<?php echo $full_url ?>">
					<div class="input-group">
						<select name="DealerSearch">
							<?php while ($shop_query->have_posts()) : $shop_query->the_post();
							?>
								<option value="<?php echo get_the_ID() ?>" <?php echo $GetDealerSearch == get_the_ID() ? 'selected' : '' ?>>
									<?php echo get_the_title() ?>
								</option>
							<?php endwhile; ?>
						</select>
						<?php if ($dealer_product_groups) : ?>
							<select name="GroupProduct">
								<option value=""><?php echo _e('Chọn nhóm sản phẩm', 'canhcamtheme') ?></option>
								<?php foreach ($dealer_product_groups as $item) : ?>
									<option value="<?php echo $item['value'] ?>" <?php echo $GetGroupProduct == $item['value'] ? 'selected' : '' ?>>
										<?php echo $item['title'] ?>
									</option>
								<?php endforeach; ?>
							</select>
						<?php endif; ?>
						<div class="button-group">
							<button class="btn-solid btn-submit" type="submit">
								<i class="fa-light fa-search"></i>
								<span><?php echo _e('Tìm kiếm', 'canhcamtheme') ?></span>
							</button>
						</div>
					</div>
				</form>
			</div>

			<div class="dealer-result-wrap mt-10">
				<h2 class="sub-header-24 font-bold text-primary-yellow uppercase text-center">
					<?php echo _e('Danh sách cửa hàng', 'canhcamtheme') ?>
				</h2>
				<?php
				// Get info shop
				if ($GetDealerSearch) {
					$shopPostIn = array($GetDealerSearch);
					$shop_args['post__in'] = $shopPostIn;
				}
				$shop_acf_query = new WP_Query($shop_args);
				?>
				<?php
				$shop_acf_query->the_post();
				$shop_acf_id = get_the_ID();
				$shop_info_list = get_field('shop_info', $shop_acf_id);

				if ($GetGroupProduct) {
					// filter product group by group product
					$shop_info_list = array_filter($shop_info_list, function ($item) use ($GetGroupProduct) {
						$group_product = array_filter($item['product_groups'], function ($group) use ($GetGroupProduct) {
							return $group['value'] == $GetGroupProduct;
						});
						return count($group_product) > 0;
					});
				}

				// filter shop group by zone
				$zone_list = array_map(function ($item) {
					return $item['zone'];
				}, $shop_info_list);
				$zone_list = array_unique($zone_list);
				// var_dump($zone_list);
				// sort by zone and group $shop_info_list
				// $shop_info_list = array_values($shop_info_list);
				// usort($shop_info_list, function ($a, $b) {
				// 	return $a['zone'] <=> $b['zone'];
				// });

				?>
				<?php
				foreach ($zone_list as $zone_item) :
				?>
					<div class="dealer-result-box">
						<h3 class="sub-header-24 font-bold text-primary-yellow">
							<?php echo $zone_item ?>
						</h3>
						<div class="dealer-result">
							<?php
							// filter shop by zone
							$new_shop_info_list = array_filter($shop_info_list, function ($item) use ($zone_item) {
								return $item['zone'] == $zone_item;
							});
							foreach ($new_shop_info_list as $key_item => $shop_item) :
							?>
								<div class="dealer-item">
									<h3 class="title">
										<?php echo $shop_item['name'] ?>
									</h3>
									<p class="address">
										<?php echo $shop_item['address'] ?>
									</p>
									<?php if ($shop_item['phone_groups']) : ?>
										<table>
											<?php foreach ($shop_item['phone_groups'] as $key => $phone_item) : ?>
												<tr>
													<td>
														<strong><?php echo $key == 0 ? _e('Số điện thoại', 'canhcamtheme') . ':' : '' ?></strong>
													</td>
													<td>
														<a href="tel: <?php echo $phone_item['phone'] ?>">
															<?php echo $phone_item['phone'] ?>
														</a>
													</td>
												</tr>
											<?php endforeach; ?>
										</table>
									<?php else : ?>
										<table>
											<tr>
												<td>
													<strong><?php echo _e('Số điện thoại', 'canhcamtheme') ?>:</strong>
												</td>
												<td>
													<a href="tel: <?php echo $shop_item['phone'] ?>">
														<?php echo $shop_item['phone'] ?>
													</a>
												</td>
											</tr>
										</table>
									<?php endif; ?>
									<p class="group">
										<strong><?php echo _e('Nhóm sản phẩm', 'canhcamtheme') ?>: </strong>
										<?php foreach ($shop_item['product_groups'] as $key => $group) : ?><span><?php echo $key == 0 ? $group['label'] : ', ' . $group['label'] ?></span><?php endforeach; ?>
									</p>
									<?php if ($shop_item['maps']) : ?>
										<div class="button">
											<a class="btn-solid" href="<?php echo '#modal-dealer-' . $shop_acf_id . $key_item  ?>" data-fancybox="">
												<i class="fa-light fa-map"></i>
												<span><?php echo _e('Xem bản đồ', 'canhcamtheme') ?></span>
											</a>
										</div>
										<div class="modal modal-lg modal-dealer" id="<?php echo 'modal-dealer-' . $shop_acf_id . $key_item  ?>">
											<div class="modal-wrap">
												<div class="modal-body">
													<div class="iframe-scale">
														<?php echo $shop_item['maps'] ?>
													</div>
												</div>
											</div>
										</div>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php
		endif;
		wp_reset_postdata();
		?>
	</div>
<?php endif; ?>
