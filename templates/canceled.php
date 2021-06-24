<?php get_header(); //phpcs:ignore Internal.LineEndings.Mixed ?>

<style>
		body{
			margin: 0 auto;
			font-family: sans-serif;
		}
		p{
			margin: 0px;
			margin-bottom: 30px;
			color: #707070;
			line-height: 1.5;
		}
		.btn{
			display: inline-block;
		}
		.wrap{
			text-align: center;
			width: 80%;
			margin:0 auto;
			padding: 80px 40px;
		}
		.icon {
			height: 100px;
			width: 100px;
			margin: 0 auto;
			margin-bottom: 30px;
		}
		.icon .success{
			fill: none;
			stroke: #4caf50;
			height: 100%;
			width: 100%;
		}
		.icon .error{
			fill: #e50000;
		}
		#icon-61-warning{
			fill:#e58900;
		}
		p.response {
				font-size: 60px;
				margin-bottom: 30px;
				font-weight: bold;
			}
			.btn {
				display: inline-block;
				text-decoration: none;
				transition: .4s;
			}
			.btn-success {
				background: #4caf50;
				color: #fff;
				margin-bottom: 30px;
				padding: 15px 35px;
				box-shadow: 2px 2px 12px rgba(0,0,0,0.3);
				text-transform: uppercase;
			}
			.btn-success:hover{
				background: #348f38;
				box-shadow: 2px 2px 8px rgba(0,0,0,0.3);
			}
			.btn-error {
				background: #e50000;
				color: #fff;
				margin-bottom: 30px;
				padding: 15px 35px;
				box-shadow: 2px 2px 12px rgba(0,0,0,0.3);
				text-transform: uppercase;
			}
			.btn-error:hover{
				background: #b40303;
				box-shadow: 2px 2px 8px rgba(0,0,0,0.3);
			}
			.btn-warn {
				background: #e58900;
				color: #fff;
				margin-bottom: 30px;
				padding: 15px 35px;
				box-shadow: 2px 2px 12px rgba(0,0,0,0.3);
				text-transform: uppercase;
			}
			.btn-warn:hover{
				background: #d17d00;
				box-shadow: 2px 2px 8px rgba(0,0,0,0.3);
			}

			.btn-back{
				color: #707070;
			}
			.btn-back:hover{
				color: #000;
			}
			.btn-back span{
				margin-right: 5px;
			}
			/*media queries*/
			@media(max-width: 768px){
				.icon{
					height: 70px;
					width: 70px;
				}
				p.response{
					font-size: 42px;
				}
			}
			@media(max-width: 475px){
				p{
					margin-bottom: 20px;
				}
				.icon{
					height: 50px;
					margin-bottom: 20px;
				}
				p.response{
					font-size: 36px;
					margin-bottom: 20px;
				}
				.btn-success{
					padding: 12px 25px;
					font-size: 13px;
					margin-bottom: 20px;
				}

			}
			/*.btn-back:hover span{
				margin-right: 8px;
			}*/
	</style>


	<!-- error template -->

	<section class="wrap">
		<div class="icon">
			<svg class="error" version="1.1" viewBox="0 0 512 512"  xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g class="st2" id="layer"><g class="st0"><line class="st1" x1="169.449" x2="342.551" y1="169.449" y2="342.551"/><line class="st1" x1="342.551" x2="169.449" y1="169.449" y2="342.551"/></g><g class="st0"><path d="M256,59c26.602,0,52.399,5.207,76.677,15.475c23.456,9.921,44.526,24.128,62.623,42.225    c18.098,18.098,32.304,39.167,42.226,62.624C447.794,203.601,453,229.398,453,256c0,26.602-5.206,52.399-15.475,76.677    c-9.922,23.456-24.128,44.526-42.226,62.623c-18.097,18.098-39.167,32.304-62.623,42.226C308.399,447.794,282.602,453,256,453    c-26.602,0-52.399-5.206-76.676-15.475c-23.457-9.922-44.526-24.128-62.624-42.226c-18.097-18.097-32.304-39.167-42.225-62.623    C64.207,308.399,59,282.602,59,256c0-26.602,5.207-52.399,15.475-76.676c9.921-23.457,24.128-44.526,42.225-62.624    c18.098-18.097,39.167-32.304,62.624-42.225C203.601,64.207,229.398,59,256,59 M256,43C138.363,43,43,138.363,43,256    s95.363,213,213,213s213-95.363,213-213S373.637,43,256,43L256,43z"/></g></g><g id="expanded"><g><path d="M267.314,256l80.894-80.894c3.124-3.124,3.124-8.189,0-11.313c-3.125-3.124-8.189-3.124-11.314,0L256,244.686    l-80.894-80.894c-3.124-3.124-8.189-3.124-11.313,0c-3.125,3.124-3.125,8.189,0,11.313L244.686,256l-80.894,80.894    c-3.125,3.125-3.125,8.189,0,11.314c1.562,1.562,3.609,2.343,5.657,2.343s4.095-0.781,5.657-2.343L256,267.314l80.894,80.894    c1.563,1.562,3.609,2.343,5.657,2.343s4.095-0.781,5.657-2.343c3.124-3.125,3.124-8.189,0-11.314L267.314,256z"/><path d="M256,59c26.602,0,52.399,5.207,76.677,15.475c23.456,9.921,44.526,24.128,62.623,42.225    c18.098,18.098,32.304,39.167,42.226,62.624C447.794,203.601,453,229.398,453,256c0,26.602-5.206,52.399-15.475,76.677    c-9.922,23.456-24.128,44.526-42.226,62.623c-18.097,18.098-39.167,32.304-62.623,42.226C308.399,447.794,282.602,453,256,453    c-26.602,0-52.399-5.206-76.676-15.475c-23.457-9.922-44.526-24.128-62.624-42.226c-18.097-18.097-32.304-39.167-42.225-62.623    C64.207,308.399,59,282.602,59,256c0-26.602,5.207-52.399,15.475-76.676c9.921-23.457,24.128-44.526,42.225-62.624    c18.098-18.097,39.167-32.304,62.624-42.225C203.601,64.207,229.398,59,256,59 M256,43C138.363,43,43,138.363,43,256    s95.363,213,213,213s213-95.363,213-213S373.637,43,256,43L256,43z"/></g></g>
			</svg>
		</div>
		<div class="msg">
			<p class="response"><?php esc_html_e( 'Canceled !', 'hbl-payment-for-woocommerce' ); ?></p>
			<p class="desc"><?php esc_html_e( 'We are so sorry to see that you have canceled the payment. Please do let us know if we could help you change your decision.', 'hbl-payment-for-woocommerce' ); ?></p>
			<?php if ( isset( $_REQUEST['invoiceNo'] ) ) : //phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<p class="desc">You Order Id is: <?php echo $_REQUEST['invoiceNo']; //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.EscapeOutput.OutputNotEscaped
				 ?></p>
				<p class="desc"><?php esc_html_e( 'Please save this number so that we can verify it later.', 'hbl-payment-for-woocommerce' ); ?></p>
			<?php endif; ?>
		</div>
		<div class="btn-sec">
			<a href="<?php echo esc_url( get_site_url() ); ?>" class="btn btn-back"><span>&larr;</span><?php esc_html_e( 'Back to home', 'hbl-payment-for-woocommerce' ); ?></a>
		</div>
	</section>

<?php get_footer(); ?>
