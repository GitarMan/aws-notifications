<?php
/**
 * Plugin Name:  AWS SES Email Notifications Plugin
 * Plugin URI:   http://ronholt.info/
 * Description:  Email users when new posts are published or the home page is updated.
 * Version:      1.0
 * Author:       Ron Holt
 * Author URI:   http://ronholt.info/
 * Text Domain:  aws-notifications
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

include_once('settings.php');

add_action(  'transition_post_status',  'awsn_notification_logic', 10, 3 );

/*
 * Currently variables in the awsn_notification_logic function such as 
 * $sender_email have been hard-coded.
 *
 * This function will need to be customized to fit the needs of your site and 
 * AWS settings.
 *
 * In the future, these variables could be added to the Settings panel to make this
 * plugin suitable for general use. 
 */
function awsn_notification_logic( $new_status, $old_status, $post ) {
    
    if ( $new_status != 'publish' ) { return; }

    // If new blog post is published
    if ( $post->post_type == 'post' &&
         $old_status != 'publish' ) {

        $sender_email = '"Site Notifications" <NOREPLY@YOURSITE.COM>';
        $email_list = awsn_email_array();
        $subject = 'New Post: ' . $post->post_title;
        $body = awsn_email_body( $post, $subject );
        $html_body = $body['html'];
        $plaintext_body = $body['plaintext'];
        awsn_send_email( $sender_email, 
                        $email_list, 
                        $subject, 
                        $html_body, 
                        $plaintext_body );
    }

    // If homepage is updated
    $frontpage_id = get_option( 'page_on_front' );
    if ( $post->post_type == 'page' &&
         $post->ID == $frontpage_id ) {
        
        if ( awsn_homepage_updated_recently() === false ) {
            $sender_email = '"Site Notifications" <NOREPLY@YOURSITE.COM>';
            $email_list = awsn_email_array();
            $subject = 'New Homepage Update';
            $body = awsn_email_body( $post, $subject );
            $html_body = $body['html'];
            $plaintext_body = $body['plaintext'];
            awsn_send_email( $sender_email, 
                            $email_list, 
                            $subject, 
                            $html_body, 
                            $plaintext_body );
        }
    }

}

/* 
 * awsn_email_body() is a helper function
 *
 * It is assumed that awsn_email_body() is called from within the 
 * awsn_notification_logic() function.
 *
 * It is either called for a new blog post, or to retrieve 
 * specific information from the home page. 
 *
 * That is why this function uses only post_type to differentiate
 * between the two uses.
 *
 * Currently variables such as $home_notifications_html are hard-coded
 * for the IDs of the specific nodes on the client's homepage. Obviously
 * this function would need to be customized for any other site.
 *
 * In the future, could add a way for the email template to be customized 
 * in the Settings panel. 
 */ 
function awsn_email_body( $post, $subject ) {

    $permalink = get_permalink( $post->ID );
    $post_content = apply_filters( 'the_content', $post->post_content );

    if ( $post->post_type === 'page' ) {

        $home_notifications_html = get_node_html( $post_content, 'home-notifications' );
        $home_daily_reports_html = get_node_html( $post_content, 'home-daily-reports' );

        $home_notifications_plaintext = get_node_plaintext( $post_content, 'home-notifications' );
        $home_daily_reports_plaintext = get_node_plaintext( $post_content, 'home-daily-reports' );


        $plaintext_body = $subject . "\n\n".
            "You can view it at the following URL:\n".
            $permalink . "\n\n" .
            "Below is a plain text preview of the updated content. Visit the URL above for full content.\n\n" .
            '-----' .
            $home_notifications_plaintext . "\n\n" .
            $home_daily_reports_plaintext;

        $html_body =  '<h1>'. $subject  .'</h1>'.
            '<h2><a href="'. $permalink .'">Click Here to view full post: '. $post->post_title .'</a></h2>'.
            '<h3>Post Content</h3>' .
            '<p>Below is a preview of the updated content. It may be incomplete or not be formatted for email. To view full page content, please visit ' . $permalink  . '</p><br><hr><br>' .
            $home_notifications_html . '<br><br>' .
            $home_daily_reports_html;
    }

    if ( $post->post_type === 'post' ) {

        $post_doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $post_doc->loadHTML( $post_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $plaintext_body = $subject . "\n\n".
            "You can view it at the following URL:\n".
            $permalink . "\n\n" .
            "Below is a plain text preview of the updated content. Visit the URL above for full content.\n\n" .
            '-----' .
            $post_doc->textContent;

        $html_body =  '<h1>'. $subject  .'</h1>'.
            '<h2><a href="'. $permalink .'">Click Here to view full post: '. $post->post_title .'</a></h2>'.
            '<h3>Post Content</h3>' .
            '<p>Below is a preview of the updated content. It may be incomplete or not be formatted for email. To view full page content, please visit ' . $permalink  . '</p><br><hr><br>' .
            $post_content;
    }

    return array( 'html' => $html_body,
                  'plaintext' => $plaintext_body );
}



function get_node_html( $post_content, $node_id ) {

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML( $post_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $xpath = new DOMXPath( $doc );
    $nlist = $xpath->query( '//div[@id="' . $node_id . '"]' );

    $output_html = preg_replace( '/(\[bg_collapse.*\])|(\[\/bg_collapse\])|(Show More\.\.\.)/', 
                                 '', 
                                 $doc->saveHTML( $nlist[0] )
                               );
    return $output_html;
}


function get_node_plaintext( $post_content, $node_id ) {

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML( $post_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $xpath = new DOMXPath( $doc );
    $nlist = $xpath->query( '//div[@id="' . $node_id . '"]' );

    $output_text = preg_replace( '/(\[bg_collapse.*\])|(\[\/bg_collapse\])|(Show More\.\.\.)/', 
                                 '', 
                                 $nlist[0]->textContent
                               );
    return $output_text;
}


// Set transient so people don't get multiple emails 
// if homepage is updated multiple times per day
function awsn_homepage_updated_recently() {
    // Get transient variable before updating
    $homepage_updated_recently = get_transient( 'homepage_updated_recently' );

    // Update transient variable 
    set_transient( 'homepage_updated_recently', 
                 true, 
                 12 * HOUR_IN_SECONDS );

    return $homepage_updated_recently;
}


function awsn_email_array() {

    $email_list = array(); 
    $users = get_users(); 
    foreach ( $users as $user ) {
        array_push( $email_list, $user->user_email );
    }

   return $email_list; 
}

function awsn_aws_credentials() {
    $settings = get_option( 'awsn_settings' );
    return array( 
                'key'    => $settings['awsn_text_field_0_aws_key'],
                'secret' => $settings['awsn_text_field_1_aws_secret']
                );
}


function awsn_send_email( $sender_email,
                          $email_list, 
                          $subject, 
                          $html_body, 
                          $plaintext_body ) {

    $char_set = 'UTF-8';
    $settings = awsn_aws_credentials();
    $SesClient = new SesClient([
        'version' => '2010-12-01',
        'region'  => 'us-west-2',
        'credentials' => [
            'key'    => $settings['key'],
            'secret' => $settings['secret']
        ]
    ]);

    foreach ( $email_list as $email ) {

        try {
            $result = $SesClient->sendEmail([
                'Destination' => [
                    'ToAddresses' => [$email],
                ],
                'ReplyToAddresses' => [$sender_email],
                'Source' => $sender_email,
                'Message' => [
                  'Body' => [
                      'Html' => [
                          'Charset' => $char_set,
                          'Data' => $html_body,
                      ],
                      'Text' => [
                          'Charset' => $char_set,
                          'Data' => $plaintext_body,
                      ],
                  ],
                  'Subject' => [
                      'Charset' => $char_set,
                      'Data' => $subject,
                  ],
                ],
            ]);
            
            // Uncomment for debugging. 
            // No need to add a success message to the error log normally.
            // $messageId = $result['MessageId'];
            // error_log("Email sent! Message ID: $messageId"."\n");

        } catch (AwsException $e) {
            error_log( $e->getMessage() );
            error_log( "The email was not sent. Error message: \n".$e->getAwsErrorMessage()."\n" );
        } 
    }
}

