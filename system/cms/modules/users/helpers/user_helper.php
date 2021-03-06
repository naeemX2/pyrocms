<?php

/**
 * User Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		Phil Sturgeon
 */

/**
 * Checks to see if a user is logged in or not.
 * 
 * @return bool
 */
function is_logged_in()
{
    return (isset(ci()->current_user)); 
}

/**
 * Checks if a group has access to module or role
 * 
 * @param string $module sameple: pages
 * @param string $role sample: put_live
 * @return bool
 * @deprecated Use $this->current_user->hasAccess()
 */
function group_has_role($module, $role)
{
	if ( ! is_logged_in()) {
		return false;
	}

	return ci()->current_user->hasAccess("{$module}.{$role}");
}

/**
 * Checks if role has access to module or returns error 
 * 
 * @param string $module sample: pages
 * @param string $role sample: edit_live
 * @param string $redirect_to (default: 'admin') Url to redirect to if no access
 * @param string $message (default: '') Message to display if no access
 * @return mixed
 */
function role_or_die($module, $role, $redirect_to = 'admin', $message = '')
{
	if (ci()->input->is_ajax_request() and ! group_has_role($module, $role)) {
		echo json_encode(array('error' => ($message ?: lang('cp:access_denied')) ));
		return false;

	} elseif ( ! group_has_role($module, $role)) {
		ci()->session->set_flashdata('error', ($message ?: lang('cp:access_denied')) );
		redirect($redirect_to);
	}
	return true;
}

/**
 * Return a users display name based on settings
 *
 * @param int $user the users id
 * @param string $linked if true a link to the profile page is returned, 
 *                       if false it returns just the display name.
 * @return  string
 */
function user_displayname($user, $linked = true)
{
    // User is numeric and user hasn't been pulled yet isn't set.
    if (is_numeric($user)) {
        User::find($user);
    }

    $name = $user->display_name ?: $user->username;

    // Static var used for cache
    if ( ! isset($_users)) {
        static $_users = array();
    }

    // check if it exists
    if (isset($_users[$user->id])) {
        if( ! empty( $_users[$user->id]->profile_link ) and $linked) {
            return $_users[$user->id]->profile_link;
        } else {
            return $name;
        }
    }

    // Set cached variable
    if (Settings::get('enable_profiles') and $linked) {
        $_users[$user->id]->profile_link = anchor('user/'.$user->username, $name);
        return $_users[$user->id]->profile_link;
    }

    // Not cached, Not linked. get_user caches the result so no need to cache non linked
    return $name;
}

/**
 * Whacky old password hasher
 *
 * @param int    $identity  The users identity
 * @param string $password  The password provided to attempt a login
 * @return  string
 * @deprecated 
 * Nobody gets to make fun of me for this. We're deleting Ion Auth and 
 * had to keep the logic around for the lifetime of 2.3. When upgrading to 3.0
 * this will go away and users STILL using old-style passwords will need to do
 *  a "Forgot Password" to get in. More on this later. Phil
 */
function whacky_old_password_hasher($identity, $password)
{
    if ( ! isset($identity, $password)) {
        return false;
    }

    $salt = ci()->pdb
        ->table('users')
        ->select('salt')
        ->whereRaw('(username = ? OR email = ?)', array($identity, $identity))
        ->take(1)
        ->pluck('salt');

    if ( ! $salt) {
        return false;
    }

    return sha1($password.$salt);
}

/* End of file users/helpers/user_helper.php */