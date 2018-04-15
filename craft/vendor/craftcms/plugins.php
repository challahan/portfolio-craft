<?php

$vendorDir = dirname(__DIR__);

return array (
  'craftcms/redactor' => 
  array (
    'class' => 'craft\\redactor\\Plugin',
    'basePath' => $vendorDir . '/craftcms/redactor/src',
    'handle' => 'redactor',
    'aliases' => 
    array (
      '@craft/redactor' => $vendorDir . '/craftcms/redactor/src',
    ),
    'name' => 'Redactor',
    'version' => '1.1.0',
    'description' => 'Edit rich text content in Craft CMS using Redactor by Imperavi.',
    'developer' => 'Pixel & Tonic',
    'developerUrl' => 'https://pixelandtonic.com/',
    'developerEmail' => 'support@craftcms.com',
    'documentationUrl' => 'https://github.com/craftcms/redactor',
    'changelogUrl' => 'https://raw.githubusercontent.com/craftcms/redactor/master/CHANGELOG.md',
    'downloadUrl' => 'https://github.com/craftcms/redactor/archive/master.zip',
  ),
  'wbrowar/adminbar' => 
  array (
    'class' => 'wbrowar\\adminbar\\AdminBar',
    'basePath' => $vendorDir . '/wbrowar/adminbar/src',
    'handle' => 'admin-bar',
    'aliases' => 
    array (
      '@wbrowar/adminbar' => $vendorDir . '/wbrowar/adminbar/src',
    ),
    'name' => 'Admin Bar',
    'version' => '3.1.5',
    'schemaVersion' => '3.1.0',
    'description' => 'Front-end shortcuts for clients logged into Craft CMS.',
    'developer' => 'Will Browar',
    'developerUrl' => 'https://wbrowar.com/plugins/adminbar',
    'changelogUrl' => 'https://raw.githubusercontent.com/wbrowar/craft-3-adminbar/master/CHANGELOG.md',
    'hasCpSettings' => true,
    'hasCpSection' => false,
    'components' => 
    array (
      'bar' => 'wbrowar\\adminbar\\services\\Bar',
      'editLinks' => 'wbrowar\\adminbar\\services\\EditLinks',
    ),
  ),
  'mikestecker/craft-videoembedder' => 
  array (
    'class' => 'mikestecker\\videoembedder\\VideoEmbedder',
    'basePath' => $vendorDir . '/mikestecker/craft-videoembedder/src',
    'handle' => 'video-embedder',
    'aliases' => 
    array (
      '@mikestecker/videoembedder' => $vendorDir . '/mikestecker/craft-videoembedder/src',
    ),
    'name' => 'Video Embedder',
    'version' => '1.0.9',
    'schemaVersion' => '1.0.0',
    'description' => 'Craft plugin to generate an embed URL from a YouTube or Vimeo URL.',
    'developer' => 'Mike Stecker',
    'developerUrl' => 'http://github.com/mikestecker',
    'documentationUrl' => 'https://github.com/mikestecker/craft-videoembedder/blob/v1/README.md',
    'changelogUrl' => 'https://raw.githubusercontent.com/mikestecker/craft-videoembedder/v1/CHANGELOG.md',
    'hasCpSettings' => false,
    'hasCpSection' => false,
  ),
  'nystudio107/craft-seomatic' => 
  array (
    'class' => 'nystudio107\\seomatic\\Seomatic',
    'basePath' => $vendorDir . '/nystudio107/craft-seomatic/src',
    'handle' => 'seomatic',
    'aliases' => 
    array (
      '@nystudio107/seomatic' => $vendorDir . '/nystudio107/craft-seomatic/src',
    ),
    'name' => 'SEOmatic',
    'version' => '3.0.0-beta.22',
    'description' => 'SEOmatic facilitates modern SEO best practices & implementation for Craft CMS 3. It is a turnkey SEO system that is comprehensive, powerful, and flexible.',
    'developer' => 'nystudio107',
    'developerUrl' => 'https://nystudio107.com',
    'changelogUrl' => 'https://raw.githubusercontent.com/nystudio107/craft-seomatic/v3/CHANGELOG.md',
    'hasCpSettings' => true,
    'hasCpSection' => true,
    'components' => 
    array (
      'frontendTemplates' => 'nystudio107\\seomatic\\services\\FrontendTemplates',
      'helper' => 'nystudio107\\seomatic\\services\\Helper',
      'jsonLd' => 'nystudio107\\seomatic\\services\\JsonLd',
      'link' => 'nystudio107\\seomatic\\services\\Link',
      'metaBundles' => 'nystudio107\\seomatic\\services\\MetaBundles',
      'metaContainers' => 'nystudio107\\seomatic\\services\\MetaContainers',
      'redirects' => 'nystudio107\\seomatic\\services\\Redirects',
      'script' => 'nystudio107\\seomatic\\services\\Script',
      'sitemaps' => 'nystudio107\\seomatic\\services\\Sitemaps',
      'tag' => 'nystudio107\\seomatic\\services\\Tag',
      'title' => 'nystudio107\\seomatic\\services\\Title',
    ),
  ),
);
