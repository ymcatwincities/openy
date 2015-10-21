Default content
===============

A default content solution for Drupal 8

How does it work
================

Enable default content first.
Any module that requires default content can put hal+json versions of the entities inside content/{entity_type} folders.

For example see default_content_test which has

* modules/default_content_test/content
* modules/default_content_test/content/node
* modules/default_content_test/content/node/imported.json
* modules/default_content_test/content/taxonomy_term
* modules/default_content_test/content/taxonomy_term/tag.json

At the moment these files need to be hand-created or exported using the Rest, Hal and Serialization modules.
Note that the default functionality of the Hal module is to make all links point to the origin site's FDQN.

The default_content module expects these (at this stage) to be relative to http://drupal.org as there is no point in having default content that can only be re-imported on the originating site.

Note that imported.json contains a node with a term reference field that includes a reference to the term in tag.json.

The Gliph library (in 8.x core) is used to resolve the dependency graph, so in this case the term is imported first so that the reference to it is created in the node.

To do
=====

UI for easily exporting?

[![Build Status](https://travis-ci.org/larowlan/default_content.svg?branch=8.x-1.x)](https://travis-ci.org/larowlan/default_content)
