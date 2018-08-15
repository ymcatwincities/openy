Add import social posts to site (Facebook, Instagram, Twitter)

### How configure import of posts from the social networks 

**Given:** 
As an Open Y site developer, I want to be able import posts from company's accounts form social networks (Twitter, Facebook, Instagram) and save them as content on the site.

**How to:**
- Open configuration page /admin/config/social_feed_fetcher_settings - Configuration ->  Social Feed Settings  
- Select checkbox for needed social network and add additional settings. Every social network has their own API, please read documentation for this settings on the official pages of each social networks.  
- When all settings will be filled - click on the button - Run Cron. Import is started. 