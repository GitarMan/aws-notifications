# AWS SES Email Notifications WordPress Plugin

This project is a WordPress plugin that generates an email notification for all the users of a site when a new post is published for the first time, or any time the home page is updated. 

I created this plugin for a specific client use case for one site, but it was complex enough that I wanted to be able to modify and reuse in this code in the future. There are a few ways that this plugin could be made more generic so it would be useful to any WordPress site without modifying the plugin code. 

I have made comments throughout the code for possible future feature additions that would need to be done to achieve this goal. Namely, instead of hard-coding site specifics, email templates, etc. these variables could have a Settings panel interface.

If there is demand, or if I end up reusing this code often enough, I will move forward with those features.

## Notes
- Settings Panel is added to Settings > AWS Notifications where you need to enter your AWS credentials. Your AWS key must have permissions to use the AWS Simple Email Service, and your email address / site settings must comply with their policies. For more information on setting this up, please refer to AWS documentation.
- For new posts it includes the full content of the post in the email body.
- For the home page, it only includes 2 HTML divs with specific IDs in the email body, as per the client's request. 

