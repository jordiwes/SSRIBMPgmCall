<?php
/**
 * ScnSocialAuth Configuration
 *
 * If you have a ./config/autoload/ directory set up for your project, you can
 * drop this config file in it and change the values as you wish.
 */
$settings = array(
    /**
     * Github Client id
     * 
     * Please specify the client id provided by github
     *
     * You can register a new application at:
     * https://github.com/settings/applications/new
     */
    'github_client_id' => '64ec32fe1677b09c390e',
    /**
     * Github Secret
     * 
     * Please specify the secret provided by github
     *
     * You can register a new application at:
     * https://github.com/settings/applications/new
     */
    'github_secret' =>'77d61fdfcac6cb45d9811acc15f4ecd83a68f94b',
);

/**
 * You do not need to edit below this line
 */
return array(
    'scn-social-auth' => $settings,
);