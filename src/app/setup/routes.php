<?php
	
	# Set up routes
	$routing = new Routing();

	# Default
	$routing->get("/", "TestAPI::get");

	/**
	 * Auto routing `REQUEST_METHOD`
	 * This is equal to
	 * $route->get("url", 	"class::get");
	 * $route->post("url", 	"class::post");
	 * $route->put("url", 	"class::put");
	 * $route->delete("url", class::delete");
	 */
	$routing->auto("test", "TestAPI");

	/** With parameter/s URL */
	$routing->get("foobar/:id", "TestAPI::foobar");

	/** Authentication */
	$routing->post("auth/login", "AuthAPI::login");
	$routing->post("auth/register", "AuthAPI::register");
	$routing->post("auth/check", "AuthAPI::check");
	$routing->post("auth/logout", "AuthAPI::logout");
	$routing->get("auth/user/:id", "AuthAPI::user");
	$routing->get("auth/users", "AuthAPI::users");
    $routing->get("auth/check_user_role/:id", "AuthAPI::check_user_role");
    $routing->post("auth/forgot_password", "AuthAPI::forgot_password");
    $routing->post("auth/change_password_by_token", "AuthAPI::change_password_by_token");
    $routing->post("auth/change_password", "AuthAPI::change_password");
    $routing->post("auth/send_verification_code", "AuthAPI::send_verification_code");
    $routing->post("auth/validate_verification_code", "AuthAPI::validate_verification_code");
    $routing->post("auth/reset_password", "AuthAPI::reset_password"); 
    $routing->post("auth/generate_guest_user", "AuthAPI::generate_guest_user"); 
    $routing->post("auth/register_guest_user", "AuthAPI::register_guest_user");
    $routing->post("auth/update_user", "AuthAPI::update_user");
    $routing->post("auth/update_one_signal_player_id", "AuthAPI::update_one_signal_player_id");
    // Kullanıcı profile image upload
    $routing->post("auth/user_profile_image_upload", "AuthAPI::user_profile_image_upload");

    $routing->delete("auth/delete_account", "AuthAPI::delete_account");
    $routing->post("auth/restore_account/:id", "AuthAPI::restore_account");

    // Social login or register
    $routing->post("auth/social_auth", "AuthAPI::social_auth");

    /** List */
    $routing->get("lists", "ListsAPI::get_all_user_list");
    $routing->get("lists/server_id", "ListsAPI::get_server_id");
    $routing->get("lists/:id", "ListsAPI::get_list");
    $routing->post("lists/next_server_id", "ListsAPI::createNextServerID");
    $routing->post("lists/create", "ListsAPI::created_list");
    $routing->post("lists/update/:id", "ListsAPI::update_list");
    $routing->post("lists/update_with_server_id/:server_id", "ListsAPI::update_list_with_server_id");
    $routing->delete("lists/delete_list_product/:id", "ListsAPI::delete_list_product");
    $routing->delete("lists/delete_list/:server_id", "ListsAPI::delete_list");
    $routing->post("lists/update_is_packet", "ListsAPI::update_is_packet");
    //Paylaşılmış listeye erişen kullanıcılar
    $routing->get("lists/shared_users/:id", "ListsAPI::get_shared_users");
    
    // Lists Products
    $routing->get("lists/products/get_all_list_products", "ListProductsAPI::get_all_list_products");
    $routing->get("lists/products/get_list_products", "ListProductsAPI::get_list_products");
    $routing->get("lists/products/get_product_server_id", "ListProductsAPI::get_product_server_id");
    $routing->get("lists/products/:list_id", "ListProductsAPI::get_list_products");
    $routing->post("lists/add_list_product", "ListProductsAPI::add_list_product");
    $routing->delete("lists/remove_product/:id", "ListProductsAPI::remove_list_product");
    $routing->post("lists/update_list_product", "ListProductsAPI::update_list_product");
    // List products for mobile app
    $routing->post("lists/add_list_product_mobile", "ListProductsAPI::add_list_product_mobile");
    $routing->post("lists/update_list_product_counter", "ListProductsAPI::update_list_product_counter");
    // Remove all products by list_id
    $routing->delete("lists/delete_all_products/:list_id", "ListProductsAPI::remove_all_list_products");

    /** Product */
    $routing->get("products", "ProductAPI::get_all_product");//OK
    $routing->get("products/:id", "ProductAPI::get_product");//OK
    $routing->post("products/create", "ProductAPI::create_product");//OK
    $routing->post("products/update/:id", "ProductAPI::update_product");//OK
    $routing->delete("products/delete/:id", "ProductAPI::delete_product");//OK
    $routing->get("products-with-category", "ProductAPI::get_product_with_category");//OK
    // Products with language
    $routing->get("products-with-translation/:lang_code", "ProductAPI::get_product_with_language");
    // Product translation
    $routing->get("products/translations/get/:product_id/:lang_code", "ProductTranslationAPI::get_product_translation");
    $routing->get("products/translations/all", "ProductTranslationAPI::get_product_translations");
    $routing->post("products/translations/create/:lang_code", "ProductTranslationAPI::create_product_translation");
    $routing->post("products/translations/update/:id", "ProductTranslationAPI::update_product_translation");

	/** Category */
	$routing->get("categories", "CategoryAPI::get_all_category");
	$routing->get("category/:id", "CategoryAPI::get_category");
	$routing->post("category/create", "CategoryAPI::create_category");
	$routing->post("category/update/:id", "CategoryAPI::update_category");
	$routing->delete("category/delete/:id", "CategoryAPI::delete_category");
    $routing->get("category-with-translation/:lang_code", "CategoryAPI::get_category_with_language");

    // Category translation
    $routing->get("category/translations/get/:category_id/:lang_code", "CategoryTranslationAPI::get_category_translation");
    $routing->get("category/translations/all", "CategoryTranslationAPI::get_category_translations");
    $routing->post("category/translations/create/:lang_code", "CategoryTranslationAPI::create_category_translation");
    $routing->post("category/translations/update/:id", "CategoryTranslationAPI::update_category_translation");

    /** Favorites */
    $routing->get("favorites", "FavoritesAPI::get_all_favorites");
    $routing->get("favorites/:id", "FavoritesAPI::get_favorites");
    $routing->post("favorites/create", "FavoritesAPI::create_favorites");
    $routing->post("favorites/update/:id", "FavoritesAPI::update_favorites");
    $routing->delete("favorites/delete/:id", "FavoritesAPI::delete_favorites");
    $routing->post("favorites/products/:user_id", "FavoritesAPI::get_favorite_product_by_user");

    /** Search History Product */
    $routing->post("search_history", "SearchAPI::save_search_history");
    $routing->delete("search_history_delete/:user_id/:product_id", "SearchAPI::remove_search_history_by_user");
    $routing->delete("search_history_delete_all/:user_id", "SearchAPI::remove_all_search_history_by_user");

    /*Upload Image*/
    $routing->post("upload-image", "UploadAPI::upload_image");

    /* Share List */
    $routing->post("share_list", "ShareListAPI::share_list");
    $routing->get("share_list/accepted/:id", "ShareListAPI::accepted_invite");
    $routing->get("share_list/decline/:id", "ShareListAPI::decline_invite");
    $routing->post("share_list/user_invite/:email", "ShareListAPI::get_user_invites");
    $routing->delete("share_list/delete/:id", "ShareListAPI::delete_invite");
    $routing->get("share_list/by_email/:email", "ShareListAPI::get_invites_by_email");
    $routing->post("share_list/token_check", "ShareListAPI::is_token_check");
    $routing->get("share_list/get_share_list", "ShareListAPI::get_share_list");
    $routing->get("share_list/get_all_invite", "ShareListAPI::get_invitees_by_list");

    /* Notifications */
    $routing->get("notifications/get_all_notifications", "NotificationsAPI::get_all_notifications");
    $routing->post("notifications/mark_as_read", "NotificationsAPI::mark_as_read");
    $routing->post("notifications/create_notification", "NotificationsAPI::create_notification");
    $routing->get("notifications/get_unread_count", "NotificationsAPI::get_unread_count");
    $routing->delete("notifications/delete/:id", "NotificationsAPI::delete_notification");

    /* Language */
    $routing->get("languages", "LanguageAPI::get_all_language");
    $routing->get("languages/:id", "LanguageAPI::get_language");
    $routing->post("languages/create", "LanguageAPI::create_language");
    $routing->put("languages/update/:id", "LanguageAPI::update_language");
    $routing->delete("languages/delete/:id", "LanguageAPI::delete_language");

    /*Units for countries*/
    $routing->get("units/:country_code", "UnitsAPI::get_units_by_country_code");

    /* Template */
    $routing->get("templates", "TemplateAPI::get_all_templates");
    $routing->post("template/create", "TemplateAPI::save_template");
    $routing->post("template/update/:id", "TemplateAPI::update_template");
    $routing->delete("template/delete/:id", "TemplateAPI::delete_template");

    /* Template */
    $routing->get("cimri_crawler", "CimriProductCrawlerAPI::cimri_create_product");

?>