================
nr_image_sitemap
================
This extension provides a data provider to use with the typo3/cms-seo extension, to create an image sitemap.

Usage
-----
Add extension template ``Netresearch: Image Sitemap (nr_image_sitemap)`` to page template.

Configuration
-------------
Before the first use of the extension, it must be ensured that the startpage with the lowest ID is configured as the root page. This ID defines the starting point for the creation of the sitemap by the extension.

The configuration is done in the backend template of the start page in the "Setup" section. There you will find the following settings

.. code-block:: typoscript

    plugin.tx_seo.config.xmlImagesSitemap.sitemaps.images.config.rootPage = [ID]

Image sitemap
-------------
The extension provides the new page type ``1642072014`` to generate an image sitemap.
Opening ``https://<YOUR-DOMAIN>/?type=1642072014`` returns the generated sitemap.

Call ``https://<YOUR-DOMAIN>/?type=1533906435`` to integrate the sitemap within the sitemap created by the
cms-seo extension.

