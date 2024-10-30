<?php
/**
 * @var bool $ga_valid - have google analytics access
 * @var string $ga_secret - ga secret
 * @var string $google_url - ga url for get secret key
 * @var array $google_counters - list of counters
 * @var int $ga_counter_key - current ga counter
 * @var bool $yandex_valid - have yandex metrica access
 * @var string $yandex_secret - yandex metrica secret key
 * @var string $yandex_url - yandex metrica url for get secret key
 * @var array $yandex_counters - list of counters
 * @var int $yandex_counter_id - current yandex metrica counter
 * @var array $statcounter_config - list of statcounter config
 * @var bool $openstat_valid - have google openstat access
 * @var array $openstat_counters - list of counters
 * @var array $openstat_config - list of openstat config
 * @var int $openstat_counter_id - current openstat counter
 */

if ( ! defined( 'ABSPATH' ) )
	exit();
?>

<div class="wrap">
    <h1>Analytics</h1>
    <h2>Google Analytics options</h2>
	<?php if ( ! $ga_valid && ! empty( $ga_secret ) ): ?>
        <div class="error">Google Analytics access error</div>
	<?php endif; ?>
	<?php if ( ! $yandex_valid && ! empty( $yandex_secret ) ): ?>
        <div class="error">Yandex Metrica access error</div>
	<?php endif; ?>
	<?php if ( ! $openstat_valid && ( ! empty( $openstat_config['login'] ) && ! empty( $openstat_config['password'] ) ) ): ?>
        <div class="error">Openstat access error</div>
	<?php endif; ?>

    <form name="form1" method="post" action="">
		<?php if ( ! $ga_valid ): ?>
            <p><?php _e( "Secret key:", 'menu-test' ); ?>
                <input type="text" name="google[mx-google-authkey]" value="<?php echo $ga_secret ?>" size="20">
                <a href="<?php echo $google_url ?>" target="_blank" class="button">Google secret</a>
            </p>
		<?php else: ?>
            <div style="clear: both; display: inline-block">
                <div style="float: left">
                    <select name="google[mx-google-counter]" style="width: 285px">
						<?php foreach ( $google_counters as $id => $name ): ?>
                            <option value="<?php echo $id ?>" <?php echo $id == $ga_counter_key ? "selected" : "" ?>><?php echo $name ?></option>
						<?php endforeach; ?>
                    </select>
                </div>
                <div style="float:left; clear: right"><input type="submit" name="reset_google" class="button"
                                                             value="<?php esc_attr_e( 'Reset' ) ?>"/></div>
            </div>
		<?php endif; ?>
        <hr/>
        <h2>Yandex Metrica options</h2>
		<?php if ( ! $yandex_valid ): ?>
            <p><?php _e( "Secret key:", 'menu-test' ); ?>
                <input type="text" name="yandex[mx_yandex_secret]" value="<?php echo $yandex_secret ?>" size="20">
                <a href="<?php echo $yandex_url ?>" class="button" target="_blank">Yandex secret</a>
            </p>
		<?php else: ?>
            <div style="clear: both; display: inline-block">
                <div style="float: left">
                    <select name="yandex[mx-yandex-counter]" style="width: 285px">
						<?php foreach ( $yandex_counters as $id => $name ): ?>
                            <option value="<?php echo $id ?>" <?php echo $id == $yandex_counter_id ? "selected" : "" ?>><?php echo $name ?></option>
						<?php endforeach; ?>
                    </select>
                </div>
                <div style="float:left; clear: right;">
                    <a href="https://passport.yandex.ru/profile/access" target="_blank" class="button"
                       id="yandex_reset">Reset</a>
                </div>
            </div>
		<?php endif; ?>
        <hr/>
        <h2>Statcountrer options</h2>
        <p><?php _e( "Counter ID:", 'menu-test' ); ?>
            <input type="number" name="statcounter[mx_statcounter_id]" min="1"
                   value="<?php echo $statcounter_config['id'] ?>" size="20"><br><br>
			<?php _e( "Login:", 'menu-test' ); ?>
            <input type="text" name="statcounter[statcounter_login]" value="<?php echo $statcounter_config['login'] ?>"
                   size="20">
			<?php _e( "Password:", 'menu-test' ); ?>
            <input type="password" name="statcounter[statcounter_password]"
                   value="<?php echo $statcounter_config['password'] ?>" size="20">
        </p>
        <hr/>
        <h2>Openstat options</h2>
		<?php if ( ! $openstat_valid ): ?>
            <p><?php _e( "Login:", 'menu-test' ); ?>
                <input type="text" name="openstat[login]" value="<?php echo $openstat_config['login'] ?>" size="20">
				<?php _e( "Password:", 'menu-test' ); ?>
                <input type="password" name="openstat[password]" value="<?php echo $openstat_config['password'] ?>"
                       size="20">
            </p>
		<?php else: ?>
            <div style="clear: both; display: inline-block">
                <div style="float: left">
                    <select name="openstat[mx-openstat-counter]" style="width: 285px">
						<?php foreach ( $openstat_counters as $id => $name ): ?>
                            <option value="<?php echo $id ?>" <?php echo $id == $openstat_counter_id ? "selected" : "" ?>><?php echo $name ?></option>
						<?php endforeach; ?>
                    </select>
                </div>
                <div style="float:left; clear: right;">
                    <input type="submit" name="reset_openstat" class="button"
                           value="<?php esc_attr_e( 'Reset' ) ?>"/>
                </div>
            </div>
		<?php endif; ?>
        <hr/>
        <p class="submit">
            <input type="submit" name="Submit" class="button-primary"
                   value="<?php esc_attr_e( 'Save Changes' ) ?>"/>
        </p>
    </form>
</div>