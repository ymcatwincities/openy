# Social feed Fetcher

## Introduction

- Social feed Fetcher module provides the user to fetch the data from their respective
Facebook, Twitter, and Instagram profiles and then display them accordingly as
per their requirement using the Drupal node system.

- Facebook APIs will allow you to display particular post types,
pictures, videos of your posts also the date of your post with
provision to provide number of count.

- Instagram APIs will allow you to display pictures from your
instagram profile with provision to provide number of count to be displayed
also provision to select the resolution of the image with options and you can
also provide the post link.

- Twitter APIs will allow you get the latest tweets with date
of your format and provision to provide number of count. Twitter APIs will
not work locally but only on live sites.

- This module is easy and simple to install and use if the project page
description or the README.txt file is followed correctly.

- This module is highly recommended for the both developers & non-developers
since the default layout of the nodes are plain and in simple text hence if
you're aware of working with CSS then this module will work for you like a
charm.

## Requirements

- PHP 5.4 and above.

## Installation

- Install as usual, see https://www.drupal.org/node/1897420 for further
information.

- Now, in your modules/ directory download the Social Feed Fetcher module

- Enable the Social Feed Fetcher module.
    It creates additional content type Social Post

- Ensure composer dependencies for the module are installed

## Configuration

There are configuration forms for each social media platform, which you
can access at admin/config/services/social_feed_settings.

When enabled and configured properly, this module will display the
form at /admin/config/socialfeed/social_feed_settings, after this step you can use the
nodes from Drupal system to show the feeds from their respective services.
