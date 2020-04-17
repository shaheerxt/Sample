<!-- https://api.slack.com/tutorials/your-first-slash-command -->

<!-- Incoming WebHooks
Webhook URL
$slack_webhook_url = "https://hooks.slack.com/services/T0XXXXXXX/B0XXXXXXX/xxxxxxxxxxxxxxxxxxxxxxxx"; // replace that URL with your webhook URL
https://hooks.slack.com/services/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 -->
$slack_webhook_url = "https://hooks.slack.com/services/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";

<!-- $icon_url = ""; // the URL for where you upload the image, eg http://domain.com/slackipedia/wikipedia-logo-cc-by-sa_0.png
https://a.slack-edge.com/80588/img/services/outgoing-webhook_48.png -->
$icon_url = "https://a.slack-edge.com/80588/img/services/outgoing-webhook_48.png";

$wiki_lang = "en";
$search_limit = "4";
<!-- $user_agent1 = "Slackipedia/1.0 (https://github.com/mccreath/slackipedia; mccreath@gmail.org)"; -->
$user_agent = "Slackipedia/1.0 (https://github.com/shaheerxt/Sample; mpshaheerdba@gmail.com)";
$command = $_POST['command'];
$text = $_POST['text'];
$token = $_POST['token'];
$channel_id = $_POST['channel_id'];
$user_name = $_POST['user_name'];

<!-- #
# Encode the $text for the Wikipedia search string
# -->
$encoded_text = urlencode($text);

<!-- The MediaWiki API responds to GET requests, which means that all the search values are passed to the server as part of the URL. For our search we're going to pass four values:
action, which tells the Wikipedia server what function of the API we're going to use. In our case it's going to be opensearch.
search, which is the string will be looking for. In our case it will be our $encoded_text variable.
format, which tells the Wikipedia server how we want to receive the data it sends back. We want json.
limit, which tells the Wikipedia server how many results to return to us. We're going to limit it to 4 for this tutorial. You can do any number, but bear in mind the context of a Slack channel and whether it would make sense in that context to have 10, or even 20, results. It probably wouldn't. -->

<!-- Ref#
https://en.wikipedia.org/w/api.php?action=opensearch&search=searchtext&format=json&limit=4 -->

<!-- #
# Create URL for Wikipedia API, which requires using GET -->
#

$wiki_url = "https://".$wiki_lang.".wikipedia.org/w/api.php?action=opensearch&search=".$encoded_text."&format=json&limit=".$search_limit;
$wiki_call = curl_init($wiki_url);
<!-- (It's very common to use the variable name $ch for this. It stands for curl handle and it can be used over and over for all your cURL handles. However, I like to give my cURL handles more descriptive names.) -->
curl_setopt($wiki_call, CURLOPT_RETURNTRANSFER, true);
curl_setopt($wiki_call, CURLOPT_USERAGENT, $user_agent);
curl_setopt($wiki_call, CURLOPT_FOLLOWLOCATION, true);
$wiki_response = curl_exec($wiki_call);

if($wiki_response === FALSE ){
$message_text = "There was a problem reaching Wikipedia. This might be helpful: The cURL error is " . curl_error($wiki_call);
} else {
$message_text = "";
}

curl_close($wiki_call);

$wiki_array = json_decode($wiki_response);
PHP's json_decode() function
$title = $wiki_array[1][0]
$summary = $wiki_array[2][0]
$url = $wiki_array[3][0]


if($wiki_response !== FALSE){
$wiki_array = json_decode($wiki_response);

$other_options = $wiki_array[3];
$first_item = array_shift($other_options);
$other_options_count = count($other_options);

$message_text = "<@".$user_id."|".$user_name."> searched for *".$text."*.\n";

    if (strpos($wiki_array[2][0],"may refer to:") !== false) {
    $disambiguation_check = TRUE;
    }

    $message_primary_title = $wiki_array[1][0];
    $message_primary_summary = $wiki_array[2][0];
    $message_primary_link = $wiki_array[3][0];

    if(count($wiki_array[1]) == 0){
    $message_text = "Sorry! I couldn't find anything like *".$text."*.";

    } else {
    if ($disambiguation_check == TRUE) { // see if it's a disambiguation page
    $message_text .= "There are several possible results for ";
    $message_text .= "*<".$message_primary_link."|".$text.">*.\n";
        $message_text .= $message_primary_link;
        $message_other_title = "Here are some of the possibilities:";
        } else {
        $message_text .= "*<".$message_primary_link."|".$message_primary_title.">*\n";
            $message_text .= $message_primary_summary."\n";
            $message_text .= $message_primary_link;
            $message_other_title = "Here are a few other options:";
            }

            foreach ($other_options as $value) {
            $message_other_options .= $value."\n";
            }
            } // close the `if` where we check the count of `$wiki_array[1]`
            } // close the `if` where we verify that `$wiki_response` is not FALSE





            $data = array(
            "username" => "Slackipedia",
            "channel" => $channel_id,
            "text" => $message_text,
            "mrkdwn" => true,
            "icon_url" => $icon_url,
            "attachments" => array(
            array(
            "color" => "#b0c4de",
            // "title" => $message_primary_title,
            "fallback" => $message_attachment_text,
            "text" => $message_attachment_text,
            "mrkdwn_in" => array(
            "fallback",
            "text"
            ),
            "fields" => array(
            array(
            "title" => $message_other_options_title,
            "value" => $message_other_options
            )
            )
            )
            )
            );
            $json_string = json_encode($data);

            $slack_call = curl_init($slack_webhook_url);
            curl_setopt($slack_call, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($slack_call, CURLOPT_POSTFIELDS, $json_string);
            curl_setopt($slack_call, CURLOPT_CRLF, true);
            curl_setopt($slack_call, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($slack_call, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Content-Length: " . strlen($json_string))
            );

            $result = curl_exec($slack_call);
            curl_close($slack_call);
