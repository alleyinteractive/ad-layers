<?php

class Ad_Layers_Page_Types_Tests extends Ad_Layers_UnitTestCase {
	protected $post_id, $page_id, $cat_id, $tag_id, $tax_id, $editor_id;

	public function setUp() {
		parent::setUp();

		register_taxonomy( 'test-taxonomy', 'post', array( 'label' => rand_str() ) );

		$this->editor_id = $this->factory->user->create( array( 'role' => 'editor' ) );

		$this->cat_id = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'cat-a' ) );
		$this->tag_id = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'tag-b' ) );
		$this->tax_id = $this->factory->term->create( array( 'taxonomy' => 'test-taxonomy', 'name' => 'tax-c' ) );

		$this->post_id = $this->factory->post->create( array(
			'post_title' => 'hello-world',
			'post_author' => $this->editor_id,
			'post_category' => array( $this->cat_id ),
			'tax_input' => array( 'post_tag' => 'tag-b', 'test-taxonomy' => 'tax-c' ),
			'post_date' => '2015-03-14 12:34:56',
		) );
		$this->page_id = $this->factory->post->create( array( 'post_title' => 'hello-page', 'post_type' => 'page' ) );
	}

	public function tearDown() {
		self::delete_user( $this->editor_id );
		parent::tearDown();
	}

	public function test_page_type_home() {
		$this->go_to( '/' );
		$this->assertTrue( is_home() );
		$this->assertSame( 'home', Ad_Layers::instance()->get_current_page_type() );
	}

	public function test_page_type_single_post() {
		$this->go_to( get_permalink( $this->post_id ) );
		$this->assertTrue( is_single() );
		$this->assertSame( 'post', Ad_Layers::instance()->get_current_page_type() );
	}

	public function test_page_type_single_page() {
		$this->go_to( get_permalink( $this->page_id ) );
		$this->assertTrue( is_page() );
		$this->assertSame( 'page', Ad_Layers::instance()->get_current_page_type() );
	}

	public function test_page_type_category() {
		$this->go_to( get_category_link( $this->cat_id ) );
		$this->assertTrue( is_category() );
		$this->assertSame( 'category', Ad_Layers::instance()->get_current_page_type() );
	}

	public function test_page_type_tag() {
		$this->go_to( get_tag_link( $this->tag_id ) );
		$this->assertTrue( is_tag() );
		$this->assertSame( 'post_tag', Ad_Layers::instance()->get_current_page_type() );
	}

	public function test_page_type_custom_taxonomy() {
		$this->go_to( get_term_link( $this->tax_id, 'test-taxonomy' ) );
		$this->assertTrue( is_tax() );
		$this->assertSame( 'test-taxonomy', Ad_Layers::instance()->get_current_page_type() );
	}

	public function test_page_type_author() {
		$this->go_to( get_author_posts_url( $this->editor_id ) );
		$this->assertTrue( is_author() );
		$this->assertSame( 'author', Ad_Layers::instance()->get_current_page_type() );
	}

	public function test_page_type_date() {
		$this->go_to( get_year_link( '2015' ) );
		$this->assertTrue( is_year() );
		$this->assertSame( 'date', Ad_Layers::instance()->get_current_page_type() );

		$this->go_to( get_month_link( '2015', '03' ) );
		$this->assertTrue( is_month() );
		$this->assertSame( 'date', Ad_Layers::instance()->get_current_page_type() );

		$this->go_to( get_day_link( '2015', '03', '14' ) );
		$this->assertTrue( is_day() );
		$this->assertSame( 'date', Ad_Layers::instance()->get_current_page_type() );
	}

	public function test_page_type_notfound() {
		$this->go_to( '?name=' . rand_str() );
		$this->assertTrue( is_404() );
		$this->assertSame( 'notfound', Ad_Layers::instance()->get_current_page_type() );

		$this->go_to( get_year_link( '2025' ) );
		$this->assertTrue( is_404() );
		$this->assertSame( 'notfound', Ad_Layers::instance()->get_current_page_type() );
	}

	public function test_page_type_search() {
		$this->go_to( '?s=hello' );
		$this->assertTrue( is_search() );
		$this->assertSame( 'search', Ad_Layers::instance()->get_current_page_type() );
	}

	// there doesn't seem to be a way to test this
	// public function test_page_type_default() {
	// 	$this->go_to( '/' );
	// 	$this->assertSame( 'default', Ad_Layers::instance()->get_current_page_type() );
	// }

	public function test_cpt_without_archive_page_type() {
		$post_type = rand_str( 20 );
		register_post_type( $post_type, array( 'public' => true ) );
		$cpt_id = $this->factory->post->create( array( 'post_title' => 'hello-cpt', 'post_type' => $post_type, 'label' => rand_str() ) );
		Ad_Layers::instance()->page_types = null;

		$this->go_to( get_permalink( $cpt_id ) );
		$this->assertTrue( is_singular( $post_type ) );
		$this->assertSame( $post_type, Ad_Layers::instance()->get_current_page_type() );
	}

	public function test_cpt_with_archive_page_types() {
		$post_type = rand_str( 20 );
		register_post_type( $post_type, array( 'public' => true, 'has_archive' => true ) );
		$cpt_id = $this->factory->post->create( array( 'post_title' => 'hello-cpt', 'post_type' => $post_type, 'label' => rand_str() ) );
		Ad_Layers::instance()->page_types = null;

		// Singular view
		$this->go_to( get_permalink( $cpt_id ) );
		$this->assertTrue( is_singular( $post_type ) );
		$this->assertSame( $post_type, Ad_Layers::instance()->get_current_page_type() );

		// Archive view
		$this->go_to( get_post_type_archive_link( $post_type ) );
		$this->assertTrue( is_post_type_archive( $post_type ) );
		$this->assertSame( 'archive::' . $post_type, Ad_Layers::instance()->get_current_page_type() );
	}
}
