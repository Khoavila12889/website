<?php
/**
 * Woo Extra Product Options - Field Editor
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('THWVSF_Admin_Settings_Pro')):
class THWVSF_Admin_Settings_Pro extends THWVSF_Admin_Settings {
	
	protected static $_instance = null;

	public function __construct() {
		parent::__construct('pro');
		$this->page_id = 'pro';
	}

	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function render_page(){
		$this->render_tabs();
		$this->render_content();
	}

	private function render_content(){
		$url = "https://www.themehigh.com/product/woocommerce-product-variation-swatches/?utm_source=free&utm_medium=premium_tab&utm_campaign=wpvs_upgrade_link";
		?>
		<div class="th-wrap-pro">
			<div class="th-nice-box">
			    <div class="th-ad-banner">
					<div class="th-ad-content">
						<div class="th-ad-content-container">
							<div class="th-ad-content-desc">
								<p>Showcase your product variations with precision and style – unlock advanced display options with Pro.</p>  
							</div>
							<div class="upgrade-pro-btn-div">
								<a class="btn-upgrade-pro above" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" onclick="this.classList.add('clicked')">Upgrade to Pro</a>
							</div> 
						</div>
					</div>
					<div class="th-ad-terms">
						<div class="th-ad-guarantee">
							<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/guarantee.svg'); ?>">
						</div>
						<p class="th-ad-term-head">30 DAYS MONEY BACK GUARANTEE
						<span class="th-ad-term-desc">100% Refund, if you are not satisfied.</span></p>
					</div>
				</div>
				<div class="th-wrapper-main">
					<div class="th-try-demo">
						<h3 class="trydemo-heading">Transform Your Product Pages with These Pro-Only Benefits</h3>
						<p class="try-demo-desc">Variation Swatches Pro offers advanced styling capabilities to enrich your product pages with a more engaging and vibrant visual experience. Don’t hold back — take your product pages to the next level right now!.</p>
						<div class="th-pro-btn">
							<a class="btn-get-pro" onclick="this.classList.add('clicked')" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" ><?php echo __('Get Pro', 'woo-extra-product-options');?></a>
							<a class="btn-try-demo" href="https://flydemos.com/wpvs/?utm_source=wpvs_free&utm_medium=banner&utm_campaign=try_demo"
							target="_blank" rel="noopener noreferrer" onclick="this.classList.add('clicked')" ><?php echo __('Try Demo', 'woo-extra-product-options');?></a></div>
						<!-- <img class="vedio" src="" alt="no img">  ADD vedio tutorial-->
					</div>
					<section class="th-wvs-key-feature">
						
						<h3 class="th-feautre-head">Key Features of WooCommerce Variation Swatches Pro</h3>
					
						<p class="th-feautre-desc">Upgrade to Variation Swatches Pro to unlock advanced styling, better control, and enhanced display options.</p>
						<div class="th-wvs-feature-list-ul">
							<ul class="th-wvs-feature-list">
								<li>Convert variation dropdowns to 5 swatch types</li>
								<li>Show out-of-stock variants with custom styles</li>
								<li>Generate links for specific product variations</li>
								<li>Built-in search to locate swatch designs and attributes</li>
								<li>Show swatches on cart and checkout pages</li>
								<li>Style the clear selection button</li>
								<li>Customize swatch appearance and layout</li>
								<li>Auto convert dropdown to label/button swatches</li>
								<li>Auto convert dropdown to variation image swatches</li>
								<li>Show swatches on shop, archive pages and additional info section</li>
								<li>Enable bi-color swatches</li>
								<li>Display stock left alert</li>
								<li>Choose from 3 additional tooltip styles</li>
								<li>Style featured swatches, info section, and shop swatches</li>
								<li>Style attribute descriptions</li>
								<li>Show selected variation next to label</li>
								<li>Featured attribute settings</li>
							</ul>   
						</div>
						<div class="th-get-pro">
							<div class="th-get-pro-img">
								<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/rocket.svg'); ?>">
							</div>
							<div class='th-wrapper-get-pro'>
								<div class="th-get-pro-desc">
									<p class="th-get-pro-desc-head">Switch to the Pro version and be a part of our limitless features
										<!-- <span class="th-get-pro-desc-contnt"><?php //echo __('Switch to a world of seamless checkout with an ocean of possibilities to customize.', 'woo-extra-product-options');?></span> -->
									</p>
								</div>
								<div class="th-get-pro-btn">
									<a class="btn-upgrade-pro orange" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" onclick="this.classList.add('clicked')" >Get Pro</a>
								</div>
							</div>
						</div>
					</section>
					<div class="th-star-support">
						<div class="th-user-star">
							<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/customer.png'); ?>">
							<p class="th-user-star-desc">2 Million+ Customer Base</p>
						</div>
						<div class="th-pro-support">
							<div class="th-pro-support-img">
								<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/support.svg'); ?>">
							</div>
							<p class="th-pro-support-desc">Enjoy the <em>premium support</em> experience with our dedicated support team.</p>
						</div>
						<div class="th-hor-line"></div>
					</div>
					
					<section class="th-design-types">
						<div class="th-design-types-content">
							<div class="th-design-types-left"> 
								<h3 class="th-design-types-head">Swatch Design Types</h3>
								<p class="th-design-types-desc">Upgrade to Variation Swatches Pro to unlock advanced styling, better control, and enhanced display options not available in the free version</p>
								<h3>Image with Label</h3>
								<p class="th-design-types-desc">Combines both image and label for enhanced visual and textual context</p>
							</div>
							<div class="th-design-img">
								<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/img-with-label.png'); ?>">
							</div>
						</div>
					</section>
					<div class="th-pro-features-row">
						<div class="th-pro-features-col-left">   
							<section class="th-pro-feature wvs-display">
								<div class="th-wvs-pro">
									<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/pro.svg'); ?>">
								</div>
								<div class="th-feature-head">Swatch Display Options/Swatches Appearance</div>
								<p class="th-feature-desc">The plugin provides five additional configurable layout options for displaying swatches on product pages.</p>
								<ul class="th-feature-section-list">
									<li><b>Slider: </b> Display swatches within a horizontally scrollable slider</li>
									<li><b>Accordion: </b> Stack swatches in a collapsible accordion format</li>
									<li><b>Horizontal Scroller: </b> Enable horizontal scrolling for swatch lists</li>
									<li><b>Vertical Scroller: </b> Enable vertical scrolling for longer swatch lists</li>
									<li><b>Swatch Dropdown:</b> Display swatches within a compact dropdown menu.</li>
								</ul>
								<div class="wvs-display-img-wrap">
									<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/swatch-display.png'); ?>">
								</div>
							</section>
							<section class="th-pro-feature wvs-stock-feature">
								<div class="th-wvs-pro">
									<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/pro.svg'); ?>">
								</div>
								<div class="th-feature-head">Stock and Availability Settings</div>
								<p class="th-feature-desc">Control how out-of-stock items appear, keep users informed, and make the shopping experience smoother</p>
								<ul class="th-feature-section-list selection-style-list wvs-stock-list">
									<li>Customize out-of-stock variation display: blur, hide, and cross icon color (Pro)</li>
									<li>Let users click on out-of-stock variations</li>
									<li>Show how many items are left in stock</li>
									<li>Set a stock level to trigger a low stock alert</li>
									<li>Display a “Back Order” label when items are available for backorder</li>
								</ul>
							</section>
							<section class="th-pro-feature wvs-feature-note">
								<div class="th-wvs-pro">
									<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/pro.svg'); ?>">
								</div>
								<div class="th-feature-head"> Unlimited Swatch Design customization</div>
								<p class="th-feature-desc">Create and manage an unlimited number of custom swatch designs. Each design can be individually mapped to specific product attributes, allowing flexible styling and consistent visual representation across your catalog.</p>
								
							</section>
						</div>
						<div class="th-pro-features-col-right">
							<section class="th-pro-feature wvs-selection">
								<div class="th-wvs-pro">
									<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/pro.svg'); ?>">
								</div>
								<div class="th-feature-head">Hover and Selection Styling</div>
								<p class="th-feature-desc">Make your swatches stand out with smooth hover effects and clean selection styles for a more engaging shopping experience</p>
								
								<div class="th-dispaly-rule-list">
									<div class="th-feature-sub-head">Hover Style</div>
									<ul class="th-feature-section-list selection-style-list">
										<li><b>Hover Border Styling: </b> Set border color and width on hover</li>
										<li><b>Selection Hover Styling: </b> Apply border or enlarge effect on hover</li>
									</ul>
									<div class="th-feature-sub-head">Selection Style</div>
									<ul class="th-feature-section-list selection-style-list">
										<li><b>Selection Border Style: </b> Set border color and width for selected swatches.</li>
										<li><b>Color/Image Selection Style: </b> Choose between border highlight or checkmark with border on selection.</li>
										<li><b>Button/Label Selection Style: </b> Choose border or checkmark with border, and customize background and font color on selection.</li>
									</ul>
								</div>
								<div class="feature-desc-imgs">
									<div class="sel-style-box-1">
										<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/sel-style-1.png'); ?>" alt="Selection Style 1">
									</div>
									<div class="sel-style-box-2">
										<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/sel-style-2.png'); ?>" alt="Selection Style 2">	
										<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/select.png'); ?>" alt="Selection Style Tick" class="overlay-tick">
									</div>
								</div>
							</section>

							<section class="th-pro-feature wvs-tt-style" style="padding-bottom: 30px;">
								<div class="th-wvs-pro">
									<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/pro.svg'); ?>">
								</div>
								<div class="th-feature-head">Tooltip Styling</div>
								<p class="th-feature-desc">Style your tooltips your way so they match your brand and look great on hover. There are three types of tooltips available:</p>
								<ul class="th-feature-section-list wvs-tooltip-list">
									<li>Image Tooltip</li>
									<li>Term Tooltip</li>
									<li>Description Tooltip</li>
								</ul>
								<div class="tt-styles-row">
									 <div class="tt-style-box">
										<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/img-tt.png'); ?>" alt="Image 1">
									</div>
									<div class="tt-style-box">
										<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/term-name-tt.png'); ?>" alt="Image 2">
									</div>
									<div class="tt-style-box">
										<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/desc-tt.png'); ?>" alt="Image 3">
									</div>
								</div>
							</section>

							
				
						</div>
					</div>
					
					<div class="th-review-section">
						<div class="review-image-section">
							<div class="review-quote-img">
								<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/reviewquotes.png'); ?>">
							</div>
						</div>
						<div id="indicator" class="th-review-navigator" style="text-align:center">
							<a class="prev" onclick='plusSlides(-1)'></a>
							<a class="next" onclick='plusSlides(1)'></a>
							<span class="dot th-review-nav-btn" onclick="currentSlide(1)"></span>
							<span class="dot th-review-nav-btn" onclick="currentSlide(2)"></span>
							<span class="dot th-review-nav-btn" onclick="currentSlide(3)"></span>
							<span class="dot th-review-nav-btn" onclick="currentSlide(4)"></span>
							<span class="dot th-review-nav-btn" onclick="currentSlide(5)"></span>
						</div>
						<div class="th-user-review-section">
							<div class="th-review-quote">
							<img src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/quotes.svg'); ?>">
							</div>
							<div class="th-user-review">
								<h3 class="th-review-heading">Excellent plugin, and fantastic support</h3>
								<p class="th-review-content">This is an excellent plugin.<br>It did everything I needed.<br>And when I needed help, I was able to count on a fantastic support team.<br>Excellent reception.<br>Very worth the investment.</p>
								<p class="th-review-user-name">Guilherme Souza</p>
							</div>
						</div>
					</div>
					<section class="th-faq-tab">
						<div class="th-faq-desc">
							<h3>FAQ's</h3>
							<p class="th-faq-para">Don't worry! Here are the answer to your frequent doubt and questions. If you feel you haven't been answered relevantly, feel free to contact our efficient support team.</p>
						
						</div>
						<div class="th-faq-qstns" >
							<button class="th-accordion" onclick="thwvsfAccordionexpand(this)">
								<div class="th-accordion-qstn">
									<p>How to upgrade to the premium version of the plugin and how can I apply the license key to activate the pro plugin?</p>
									<img class="th-accordion-img" src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/blck-down-arrow.svg'); ?>">
									<img class="th-accordion-img-opn" src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/blue-down-arrow.svg'); ?>">
								</div>
								<div class="th-panel">
									<p>Please follow the steps given in the below links to purchase the plugin and activate the license.</p>
									<p>
										<a href="https://www.themehigh.com/docs/download-and-install-your-plugin/" target="_blank" rel="noopener noreferrer">https://www.themehigh.com/docs/download-and-install-your-plugin/</a><br>
									</p>
									<p class="th-faq-links">
										<a href="https://www.themehigh.com/docs/manage-license/" target="_blank" rel="noopener noreferrer">https://www.themehigh.com/docs/manage-license/</a><br>
									</p>
								</div>
							</button>                   
							<button class="th-accordion" onclick="thwvsfAccordionexpand(this)">
								<div class="th-accordion-qstn">
									<p>Do I have to keep both the free version and the pro version after buying the pro version?</p>
									<img class="th-accordion-img" src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/blck-down-arrow.svg'); ?>">
									<img class="th-accordion-img-opn" src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/blue-down-arrow.svg'); ?>">
								</div>
								<div class="th-panel">
									<p class="th-faq-answer">Please note that free and premium versions are different plugins entirely. So, you can deactivate and remove the free version of the plugin from your website, if you start using the premium version.</p>
								</div>
							</button>
							
							<button class="th-accordion" onclick="thwvsfAccordionexpand(this)">
								<div class="th-accordion-qstn">
									<p>How to migrate our configuration from the free version to the pro version?</p>
									<img class="th-accordion-img" src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/blck-down-arrow.svg'); ?>">
									<img class="th-accordion-img-opn" src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/blue-down-arrow.svg'); ?>">
								</div>
								<div class="th-panel">
									<p class="th-faq-answer">At the time when you upgrade the plugin from the free to the premium version, the free plugin settings will get automatically migrated to the premium version.
		
									Please confirm whether all swatches that you created in the free version have been migrated to the premium version after upgrading. If so you can safely deactivate and delete the free version from your site.</p>
								</div>
							</button>
							<button class="th-accordion" onclick="thwvsfAccordionexpand(this)">
								<div class="th-accordion-qstn">
									<p>Will I get a refund if the pro plugin doesn't meet my requirements?</p>
									<img class="th-accordion-img" src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/blck-down-arrow.svg'); ?>">
									<img class="th-accordion-img-opn" src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/blue-down-arrow.svg'); ?>">
								</div>
								
								<div class="th-panel">
									<p>Please note that as per our refund policy, we will provide a refund within one month from the date of purchase, if you are not satisfied with the product. Please refer to the below link for more details:
									</p>
									<p class="th-faq-answer">
										<a href="https://www.themehigh.com/refund-policy/" target="_blank" rel="noopener noreferrer">https://www.themehigh.com/refund-policy/</a><br>
									</p>
								</div>
							</button>
							<button class="th-accordion" onclick="thwvsfAccordionexpand(this)">
								<div class="th-accordion-qstn">
									<p>Can I display variation swatches on the Shop or Archive pages?</p>
									<img class="th-accordion-img" src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/blck-down-arrow.svg'); ?>">
									<img class="th-accordion-img-opn" src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/blue-down-arrow.svg'); ?>">
								</div>
								
								<div class="th-panel">
									<p>Yes! With the <b>premium version</b> of our plugin, you can easily showcase beautiful variation swatches directly on your Shop and Archive pages. 
									</p>
									
								</div>
							</button>
							<button class="th-accordion" onclick="thwvsfAccordionexpand(this)">
								<div class="th-accordion-qstn">
									<p>Can I display only one attribute (like color or size) on the Shop page?</p>
									<img class="th-accordion-img" src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/blck-down-arrow.svg'); ?>">
									<img class="th-accordion-img-opn" src="<?php echo esc_url(THWVSF_URL .'admin/assets/images/blue-down-arrow.svg'); ?>">
								</div>
								
								<div class="th-panel">
									<p> Yes, you can show a single attribute on the shop page by marking it as a <b>Featured Attribute</b> available in the premium version of our plugin 
									</p>
									
								</div>
							</button>
							
						</div>
					</section>
					<section class="switch-to-pro-tab">
						<div class="th-switch-to-pro">
							<h3 class="switch-to-pro-heading">Switch to Pro version and be a part of our limitless features</h3>
							<p>Switch to Pro and unlock access to a few of the most sought-after features on your product page and experience one-of-a-kind personalization like never before.</p>
							<!-- <div class="th-button-get-pro-link"> -->
								<a class="button-get-pro" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" onclick="this.classList.add('clicked')"><?php echo __('Get Pro', 'woo-extra-product-options');?></a> 
							<!-- </div> -->
							
						</div>
					</section>
				</div> 
			</div>
		</div>
		<?php
	}

}
endif;