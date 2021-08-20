<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of fac, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2013 Jean-Christian Denis and contributors
# contact@jcdenis.fr http://jcd.lv
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) {

	return null;
}

$core->blog->settings->addNamespace('fac');

# Not active
if (!$core->blog->settings->fac->fac_active) {

	return null;
}

# Admin behaviors
$core->addBehavior(
	'adminPostHeaders',
	array('facAdmin', 'adminPostHeaders')
);
$core->addBehavior(
	'adminPostFormItems',
	array('facAdmin', 'adminPostFormItems')
);
$core->addBehavior(
	'adminAfterPostCreate',
	array('facAdmin', 'adminAfterPostSave')
);
$core->addBehavior(
	'adminAfterPostUpdate',
	array('facAdmin', 'adminAfterPostSave')
);
$core->addBehavior(
	'adminBeforePostDelete',
	array('facAdmin', 'adminBeforePostDelete')
);
$core->addBehavior(
	'adminPostsActionsPage',
	array('facAdmin', 'adminPostsActionsPage')
);

/**
 * @ingroup DC_PLUGIN_FAC
 * @brief Linked feed to entries - admin methods.
 * @since 2.6
 */
class facAdmin
{
	/**
	 * Add javascript (toggle)
	 * 
	 * @return string HTML head
	 */
	public static function adminPostHeaders()
	{
		return dcPage::jsLoad('index.php?pf=fac/js/admin.js');
	}

	/**
	 * Add form to post sidebar
	 * 
	 * @param  ArrayObject $main_items    Main items
	 * @param  ArrayObject $sidebar_items Sidebar items
	 * @param  record      $post          Post record or null
	 */
	public static function adminPostFormItems(ArrayObject $main_items, ArrayObject $sidebar_items, $post)
	{
		global $core;

		# Get existing linked feed
		$fac_url = $fac_format = '';
		if ($post) {

			$rs = $core->meta->getMetadata(array(
				'meta_type'	=> 'fac',
				'post_id'		=> $post->post_id,
				'limit'		=> 1
			));
			$fac_url = $rs->isEmpty() ? '' : $rs->meta_id;

			$rs = $core->meta->getMetadata(array(
				'meta_type'	=> 'facformat',
				'post_id'		=> $post->post_id,
				'limit'		=> 1
			));
			$fac_format = $rs->isEmpty() ? '' : $rs->meta_id;
		}

		# Set linked feed form items
		$sidebar_items['options-box']['items']['fac'] =
			self::formFeed($core, $fac_url, $fac_format);
	}

	/**
	 * Save linked feed
	 * 
	 * @param  cursor $cur      Current post cursor
	 * @param  integer $post_id Post id
	 */
	public static function adminAfterPostSave(cursor $cur, $post_id)
	{
		global $core;

		if (!isset($_POST['fac_url']) 
		 || !isset($_POST['fac_format'])
		) {
			return null;
		}

		# Delete old linked feed
		self::delFeed($core, $post_id);

		# Add new linked feed
		self::addFeed($core, $post_id, $_POST);
	}

	/**
	 * Delete linked feed on post edition
	 * 
	 * @param  integer $post_id Post id
	 */
	public static function adminBeforePostDelete($post_id)
	{
		self::delFeed($GLOBALS['core'], $post_id);
	}

	/**
	 * Add actions to posts page combo
	 * 
	 * @param  dcCore             $core dcCore instance
	 * @param  dcPostsActionsPage $ap   dcPostsActionsPage instance
	 */
	public static function adminPostsActionsPage(dcCore $core, dcPostsActionsPage $pa)
	{
		$pa->addAction(
			array(
				__('Linked feed') => array(
					__('Add feed') => 'fac_add'
				)
			),
			array('facAdmin', 'callbackAdd')
		);

		if (!$core->auth->check('delete,contentadmin', $core->blog->id)) {

			return null;
		}
		$pa->addAction(
			array(
				__('Linked feed') => array(
					__('Remove feed') => 'fac_remove'
				)
			),
			array('facAdmin', 'callbackRemove')
		);
	}

	/**
	 * Posts actions callback to remove linked feed
	 * 
	 * @param  dcCore             $core dcCore instance
	 * @param  dcPostsActionsPage $pa   dcPostsActionsPage instance
	 * @param  ArrayObject        $post _POST actions
	 */
	public static function callbackRemove(dcCore $core, dcPostsActionsPage $pa, ArrayObject $post)
	{
		# No entry
		$posts_ids = $pa->getIDs();
		if (empty($posts_ids)) {
			throw new Exception(__('No entry selected'));
		}

		# No right
		if (!$core->auth->check('delete,contentadmin',$core->blog->id)) {
			throw new Exception(__('No enough right'));
		}

		# Delete expired date
		foreach($posts_ids as $post_id) {
			self::delFeed($core, $post_id);
		}

		dcPage::addSuccessNotice(__('Linked feed deleted.'));
		$pa->redirect(true);
	}

	/**
	 * Posts actions callback to add linked feed
	 * 
	 * @param  dcCore             $core dcCore instance
	 * @param  dcPostsActionsPage $pa   dcPostsActionsPage instance
	 * @param  ArrayObject        $post _POST actions
	 */
	public static function callbackAdd(dcCore $core, dcPostsActionsPage $pa, ArrayObject $post)
	{
		# No entry
		$posts_ids = $pa->getIDs();
		if (empty($posts_ids)) {
			throw new Exception(__('No entry selected'));
		}

		# Save action
		if (!empty($post['fac_url'])
		 && !empty($post['fac_format'])
		) {
			foreach($posts_ids as $post_id) {
				self::delFeed($core, $post_id);
				self::addFeed($core, $post_id, $post);
			}

			dcPage::addSuccessNotice(__('Linked feed added.'));
			$pa->redirect(true);
		}

		# Display form
		else {
			$pa->beginPage(
				dcPage::breadcrumb(array(
					html::escapeHTML($core->blog->name) => '',
					$pa->getCallerTitle() => $pa->getRedirection(true),
					__('Linked feed to this selection') => '' 
				))
			);

			echo
			'<form action="'.$pa->getURI().'" method="post">'.
			$pa->getCheckboxes().

			self::formFeed($core).

			'<p>'.
			$core->formNonce().
			$pa->getHiddenFields().
			form::hidden(array('action'), 'fac_add').
			'<input type="submit" value="'.__('Save').'" /></p>'.
			'</form>';

			$pa->endPage();
		}
	}

	/**
	 * Linked feed form field
	 * 
	 * @param  dcCore $core   dcCore instance
	 * @param  string $url    Feed URL
	 * @param  string $format Feed format
	 * @return string         Feed form content
	 */
	protected static function formFeed(dcCore $core, $url='', $format='')
	{
		return 
		'<div id="fac">'.
		'<h5>'.__('Linked feed').'</h5>'.
		'<p><label for="fac_url">'.
		__('Feed URL:').'</label>'.
		form::field(
			'fac_url',
			60,
			255,
			$url,
			'maximal'
		).'</p>'.
		'<p><label for="fac_format">'.
		__('Format:').'</label>'.
		form::combo(
			'fac_format',
			self::comboFac($core),
			$format,
			'maximal'
		).'</p>'.
		($url ? '<p><a href="'.$url.'" title="'.$url.'">'.__('view feed').'</a></p>' : '').
		'</div>';
	}

	/**
	 * List of fac formats
	 * 
	 * @param  dcCore $core dcCore instance
	 * @return array        List of fac formats
	 */
	protected static function comboFac(dcCore $core)
	{
		$formats = @unserialize($core->blog->settings->fac->fac_formats);
		if (!is_array($formats) || empty($formats)) {

			return array();
		}

		$res = array();
		foreach($formats as $uid => $f) {
			$res[$f['name']] = $uid;
		}

		return $res;
	}

	/**
	 * Delete linked feed
	 * 
	 * @param  dcCore  $core    dcCore instance
	 * @param  integer $post_id Post id
	 */
	protected static function delFeed(dcCore $core, $post_id)
	{
		$post_id = (integer) $post_id;
		$core->meta->delPostMeta($post_id, 'fac');
		$core->meta->delPostMeta($post_id, 'facformat');
	}

	/**
	 * Add linked feed
	 * 
	 * @param  dcCore  $core    dcCore instance
	 * @param  integer $post_id Post id
	 * @param  array   $options Feed options
	 */
	protected static function addFeed($core, $post_id, $options)
	{
		if (empty($options['fac_url']) 
		 || empty($options['fac_format'])
		) {
			return null;
		}

		$post_id = (integer) $post_id;

		$core->meta->setPostMeta(
			$post_id,
			'fac',
			$options['fac_url']
		);
		$core->meta->setPostMeta(
			$post_id,
			'facformat',
			$options['fac_format']
		);
	}
}
