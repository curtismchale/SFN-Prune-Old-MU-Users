<?php
/*
Plugin Name: SFN Prune Old Users
Plugin URI: http://sfndesign.ca
Description: Prunes WordPress Multisite users that haven't activated in 2 weeks
Version: 1.0
Author: SFNdesign, Curtis McHale, RapidMiner
Author URI: http://sfndesign.ca
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


class SFN_Prune_Old_Users{

	function __construct(){

		add_action( 'sfn_prune_old_users', array( $this, 'prune_users' ) );
		add_action( 'sfn_prune_old_users_single', array( $this, 'prune_users' ) );

		add_action( 'admin_notices', array( $this, 'check_required' ) );

		// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );

	} // construct

	/**
	 * Holds all our functions to prune old users that have not activated
	 *
	 * @since 1.0
	 * @author SFNdesign, Curtis McHale
	 * @access public
	 *
	 * @uses $this->get_not_active_users()          Returns array of objects that is the not activated users
	 * @uses $this->just_the_old_ones_please()      Returns just the users that are older than 2 weeks
	 * @uses $this->put_old_folks_on_an_iceberg()   Deletes the users from the database
	 */
	public function prune_users(){

		$not_active_users = $this->get_not_active_users();

		$old_users = $this->just_the_old_ones_please( $not_active_users );

		if ( isset( $old_users ) && ! empty( $old_users ) ){
			$this->put_old_folks_on_an_iceberg( $old_users );
		} // if

	} // prune_users

	/**
	 * This takes the old users and removes them from the database which is
	 * sort of like putting them on an iceberg if you think about it.
	 *
	 * @since 1.0
	 * @author SFNdesign, Curtis McHale
	 *
	 * @param array     $old_users     required     The old users we want to delete
	 */
	private function put_old_folks_on_an_iceberg( $old_users ){

		global $wpdb;

		foreach( $old_users as $old ){

			$query = $wpdb->prepare( "DELETE FROM $wpdb->signups WHERE user_login = %s", $old->user_login );
			$wpdb->query( $query );

		} // foreach

	} // put_old_folks_on_an_iceberg

	/**
	 * Loops through the users and figures out which are the old folks
	 *
	 * @since 1.0
	 * @author SFNdesign, Curtis McHale
	 *
	 * @param array     $users     required     The array of users to check, expects users as stdClass Objects
	 *
	 * @return array    $old_folks              The old users
	 *
	 * @filter          sfn_prune_time_ago      Allows you to change how long ago you want to prune
	 */
	private function just_the_old_ones_please( $users ){

		$old_folks = array();
		$time_ago = strtotime( '2 weeks ago', time() );
		$time_ago = apply_filters( 'sfn_prune_time_ago', $time_ago );

		foreach( $users as $u ){

			$registered_time = strtotime( $u->registered );

			if ( $time_ago >= $registered_time ){
				$old_folks[] = $u;
			}

		} // foreach

		return $old_folks;

	} // just_the_old_ones_please

	/**
	 * Gets the users that are not activated.
	 *
	 * @since 1.0
	 * @author SFNdesign, Curtis McHale
	 *
	 * @return array/obj     $users    The array of objects that are users
	 *
	 * @filter sfn_prune_how_many_users     Allows you to change the number of users to prune in a batch
	 *
	 * @uses $wpdb->prepare()          Makes our SQL queries safe
	 * @uses $wpdb->get_results()      Gets the results from the DB class
	 */
	private function get_not_active_users(){

		global $wpdb;

		$how_many = apply_filters( 'sfn_prune_how_many_users', 200 );

		$query = $wpdb->prepare( "SELECT * FROM $wpdb->signups WHERE active = %d LIMIT %d", 0, absint( $how_many ) );
		$users = $wpdb->get_results( $query );

		return $users;

	} // get_old_users

	/**
	 * Checks that we are in fact on a Multisite install and deactivates the plugin
	 * if we are not since it would be useless.
	 *
	 * @uses    is_multisite        Returns true if a mulitsite install
	 * @uses    deactivate_plugins  Deactivates plugins given string or array of plugins
	 *
	 * @action  admin_notices       Provides WordPress admin notices
	 *
	 * @since   1.0
	 * @author  SFNdesign, Curtis McHale
	 */
	public function check_required(){
		if( ! is_multisite() ){ ?>

			<div id="message" class="error">
				<p>SFN Prune Old Users does nothing if you're not running Multisite. This plugin has been deactivated.</p>
			</div>

			<?php
			deactivate_plugins( '/sfn-prune-old-users/sfn-prune-old-users.php' );
		} // is_multisite

	} // check_required

	/**
	 * Fired when plugin is activated
	 *
	 * @param   bool    $network_wide   TRUE if WPMU 'super admin' uses Network Activate option
	 *
	 * @uses wp_next_scheduled()          Gets the next event by given name
	 * @uses wp_schedule_event()          Schedules a recurring event with WP Cron
	 */
	public function activate( $network_wide ){

		$scheduled = wp_next_scheduled( 'sfn_prune_old_users' );

		if ( $scheduled == false ){
			wp_schedule_single_event( time(), 'sfn_prune_old_users_single' );
			wp_schedule_event( time(), 'daily', 'sfn_prune_old_users' );
		}

	} // activate

	/**
	 * Fired when plugin is deactivated
	 *
	 * @param   bool    $network_wide   TRUE if WPMU 'super admin' uses Network Activate option
	 */
	public function deactivate( $network_wide ){

		$scheduled = wp_next_scheduled( 'sfn_prune_old_users' );

		if ( $scheduled == true ){
			wp_clear_scheduled_hook( 'sfn_prune_old_users' );
			wp_clear_scheduled_hook( 'sfn_prune_old_users_single' );
		}

	} // deactivate

	/**
	 * Fired when plugin is uninstalled
	 *
	 * @param   bool    $network_wide   TRUE if WPMU 'super admin' uses Network Activate option
	 */
	public function uninstall( $network_wide ){

	} // uninstall

} // SFN_Prune_Old_Users

new SFN_Prune_Old_Users();
