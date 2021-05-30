<?php

/**
 * @file
 * Theme functions for the custom_media_types module.
 */

/**
 * Prepares variables for media_kaltura_embed templates.
 *
 * Default template: media-kaltura-embed.html.twig.
 */
function template_preprocess_media_kaltura_embed(&$variables) {
  $variables['playerId'] = 'kaltura-' . $variables['entryId'];

  $variables['extraFlashVars'] = '';
  $vars = [];
  foreach ($variables['flashVars'] as $k => $v) {
    if ($k == 'streamerType') {
      continue;
    }
    $vars[] = 'flashvars[' . $k . ']=' . $v;
  }
  if (count($vars)) {
    $variables['extraFlashVars'] = '&' . implode('&', $vars);
  }

  // Not sure what to do about width of playlist embeds.
  if (isset($variables['flashVars']['playlistAPI.kpl0Id'])) {
    $variables['width'] *= 2;
  }
}
