<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   CREA Listing
 * @author    Sprytechies <contact@sprytechies.com>
 * @license   GPL-2.0+
 * @link      http://sprytechies.com
 * @copyright 2014 contact@sprytechies.com
 */

$options = get_option($this->option_name);
$users = get_option($this->option_name_users); 

?>
<div class="wrap">
    <?php screen_icon(); ?>
    <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
    <h3>CREA Account Details</h3>
    <i>Enter/Update user access details to fetch CREA Listings</i>
    <form method="post" action="options.php" id="option-form">
        <?php settings_fields('crea_options');
        if(isset($_POST['update-user'])) { 
              $username=$_POST['update-user'];
              $userupdate=$users[$username]; ?>
              
              <table class="form-table">
                <tr valign="top"><th scope="row">Name:</th>
                    <td><input type="text" name="<?php echo $this->option_name?>[fullname] ?>" value="<?php echo $userupdate['fullname']; ?>" /></td>
                </tr>
                <tr valign="top"><th scope="row">Username:</th>
                    <td><input type="text" name="<?php echo $this->option_name?>[username] ?>" value="<?php echo $userupdate['username']; ?>" /></td>
                </tr>
                <tr valign="top"><th scope="row">Password:</th>
                    <td><input type="text" name="<?php echo $this->option_name?>[password] ?>" value="<?php echo $userupdate['password']; ?>" /></td>
                </tr>
                <tr valign="top"><th scope="row">Number of listings for each page:</th>
                    <td><input type="text" name="<?php echo $this->option_name?>[number_of_listing]" value="<?php echo $options['number_of_listing']; ?>" /></td>
                </tr>
            </table>

          <?php  } else {  ?>

                <table class="form-table">
                    <tr valign="top"><th scope="row">Name:</th>
                        <td><input type="text" name="<?php echo $this->option_name?>[fullname] ?>" value="" /></td>
                    </tr>
                    <tr valign="top"><th scope="row">Username:</th>
                        <td><input type="text" name="<?php echo $this->option_name?>[username] ?>" value="" /></td>
                    </tr>
                    <tr valign="top"><th scope="row">Password:</th>
                        <td><input type="text" name="<?php echo $this->option_name?>[password] ?>" value="" /></td>
                    </tr>
                    <tr valign="top"><th scope="row">Number of listings for each page:</th>
                        <td><input type="text" name="<?php echo $this->option_name?>[number_of_listing]" value="<?php echo $options['number_of_listing']; ?>" /></td>
                    </tr>
                </table>

            <?php } ?>

            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save') ?>" /><span style="margin-left:20px"><a href="javascript:void(0)" id="clear-form">Clear</a></span>
            </p>

    </form>
    <br><br>
    <h3>Short code to display Property Listings</h3>
    <i>Use the shortcode for properties display. The shortcode will be in following format</i><br><br>
    <code>[list-properties user='username']</code> <span style="font-size: 11px;margin-left: 20px"><i>e.g. [list-properties user='CXLHfDVrziCfvwgCuL8nUahC']</i></span>
    <br><br><br>
    <i>To display single property, shortcode must be in following format,</i><br><br>
    <code>[property user='username' mlsid='mls id']</code> <span style="font-size: 11px;margin-left: 20px"><i>e.g. [property user='CXLHfDVrziCfvwgCuL8nUahC' mlsid='20131772']</i></span>
    <br><br><br><br>
    <h3>Users </h3>
    <i>List of all Users in the system.</i><br><br>
    <table class="widefat">
        <thead>
            <tr>
                <th>Name</th>
                <th>Username</th>       
                <th>Password</th>
                <th>Number of listing</th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th>Name</th>
                <th>Username</th>       
                <th>Password</th>
                <th>Number of listing</th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
        </tfoot>
        <tbody>
            <?php if(isset($users) && $users){ 
                foreach($users as $user){ ?>
                    <tr>
                        
                        <td><?php echo $user['fullname']; ?></td>
                        <td><?php echo $user['username']; ?></td>
                        <td><?php echo $user['password']; ?></td>
                        <td><?php echo $user['number_of_listing']; ?></td>
                        <td><a href="javascript:void(0);" id="<?php echo $user['username']; ?>" class="view-data button-primary">View Listings</a></td>
                        <td>
                            <form method="post" action="../wp-admin/options-general.php?page=crea-listing">
                                <input type="hidden" name="update-user" value="<?php echo $user['username']; ?>">
                                <input type="submit" class="button-primary" value="Update" id="update-data">
                            </form>
                        </td>
                        <td>
                            <form method="post" action="options.php">
                                <input type="hidden" name="action" value="delete-option">
                                <input type="hidden" name="option_page" value="crea_options">
                                <input type="hidden" name="username" value="<?php echo $user['username']; ?>">
                                <input type="hidden" name="_wp_http_referer" value="../wp-admin/options-general.php?page=crea-listing">
                                <input type="hidden" id="_wpnonce" name="_wpnonce" value="ac5ec32347">
                                <input type="submit" class="button-primary" value="Delete" id="delete">
                            </form>
                        </td>
                    </tr>

               <?php } } ?>
        </tbody>
    </table>
</div>
<div class="wrap-inner" id="inner-hide" ></div>
<style>
.wrap-inner a{
    float:right;
    padding-right: 10px;
}
</style>
