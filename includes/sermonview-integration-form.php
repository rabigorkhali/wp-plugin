<?php

class SermonView_Integration_Form {
	public $buttonArr; // an array of buttons
	public $fieldArr; // an array of fields
	public $action;  // the action URI for the form
	public $type;   // type of form (post or get)
	public $post;  // the post array from a previous submission
	public $message; // a string or array of field-specific messages
	public $nonceName; // the name of the form nonce to be added to the form

	public function __construct($action,$type='post') {
		$this->setAction($action);
		$this->setType($type);
		$this->setPost(array()); // set post to blank array()
		$this->nonceName = 'defaultForm';
		$this->fieldArr = array();
		$this->buttonArr = array();
		$this->linkArr = array();
	}
	public function setAction($action) {
		$this->action = $action;
	}
	public function setType($type) {
		$this->type = $type;
	}
	public function setPost($post) {
		$this->post = $post;
	}
	public function setMessage($message) {
		$this->message = $message;
	}
	public function setNonceName($name) {
		$this->nonceName = $name;
	}
	public function addFields($fields) {
		// this takes an array of multiple single field arrays and adds it to the field list
		$this->fieldArr = array_merge($this->fieldArr,$fields);
	}
	public function addField($singleFieldArr) {
		/** this is an array with keys:
					field: the name of the field (required)
					label: the label for the field (required)
					sv_field: the matching post var for the SermonView API (required)
					type: the type of field, such as text, textarea, password, etc
					validation: pipe delimited list of validation parameters - possible parameters:
									required: field is required
									password: value must be a password, checked against a strong password algorithm
		 */
		$this->fieldArr[] = $singleFieldArr;
	}
	public function addButton($args) {
		$this->buttonArr[] = $args;
	}
	public function addLink($label,$target='submit') {
		$this->linkArr[] = array(
			'label' => $label,
			'target' => $target
		);
	}
	public function buildForm() {
?>
<form action="<?php echo $this->action; ?>" method="<?php echo $this->type; ?>">

<?php
			$first_field = true;
			foreach($this->fieldArr as $field) {
				switch($field['type']) {
					case 'divider':
						echo "\t\t" . '<hr class="form-divider" />' . "\n";
						break;
					case 'hidden':
						echo "\t\t" . '<input type="' . $field['type'] . '" name="' . $field['field'] . '" value="' . $field['value'] . '" />' . "\n";
						break;
					case 'static':
?>
	<div class="form-row">
		<label class="left"><?php echo $field['label']; ?>:</label>
		<div class="input-holder">
			<div id="<?php echo $field['field']; ?>" class="static"><?php echo $field['value'] . ($field['clear-email-link'] ? '<a href="' . wp_login_url() .'" class="clear-email"><i class="fa fa-times-circle"></i></a>' : ''); ?></div>
		</div>
	</div>

<?php
						break;
					case 'text':
					case 'password':
					default:
?>
	<div class="form-row">
		<label for="<?php echo $field['field']; ?>" class="left"><?php echo (strpos($field['validation'],'required') !== false ? '<strong>' : '') . ($field['label'] ? $field['label'] . ':' : '') . (strpos($field['validation'],'required') !== false ? '</strong>' : ''); ?></label>
		<div class="input-holder raise">
			<input type="<?php echo $field['type']; ?>" name="<?php echo $field['field']; ?>" class="svi-input-field<?php echo (is_array($this->message) && key_exists($field['field'],$this->message) ? ' error' : ''); ?>" value="<?php echo key_exists($field['field'],$this->post) ? $this->post[$field['field']] : (key_exists('default',$field) ? $field['default'] : ''); ?>" <?php echo ($first_field ? 'autofocus="autofocus" ' : '') . (strpos($field['validation'],'required') !== false ? 'required ' : ''); ?> />
<?php
						if(is_array($this->message) && key_exists($field['field'],$this->message)) {
							if(is_array($this->message[$field['field']])) {
								foreach($this->message[$field['field']] as $msg) {
									echo '<div class="input-instructions error">' . $msg . '</div>';
								}
							} else {
									echo '<div class="input-instructions error">' . $this->message[$field['field']]. '</div>';
							}
						} elseif(key_exists('instructions',$field)) {
							echo '<div class="input-instructions">' . $field['instructions'] . '</div>';
						}

?>
		</div>
	</div>
<?php
						break;
					case 'checkbox':
?>
	<div class="form-row">
		<label class="left"><?php echo (strpos($field['validation'],'required') !== false ? '<strong>' : '') . ($field['label'] ? $field['label'] . ':' : '') . (strpos($field['validation'],'required') !== false ? '</strong>' : ''); ?></label>
		<div class="input-holder custom-checkbox raised">
<?php
				if(key_exists('values',$field) && is_array($field['values'])) {
					foreach($field['values'] as $subfield) {
						echo '<input type="' . $field['type'] . '" id="' . $subfield['field'] . '" name="' . $subfield['field'] . '" value="' . $subfield['value'] . '" class="svi-input-field' . (is_array($this->message) && key_exists($subfield['field'],$this->message) ? ' error' : '') . '"' . ($subfield['default'] == $subfield['value'] ? ' checked="checked"' : '') . (strpos($field['validation'],'required') !== false ? ' required' : '') . '><label for="' . $subfield['field'] . '">' . $subfield['label'] . '</label>' . "\n";
						if(is_array($this->message) && key_exists($subfield['field'],$this->message)) {
							if(is_array($this->message[$subfield['field']])) {
								foreach($this->message[$subfield['field']] as $msg) {
									echo '<div class="input-instructions error">' . $msg . '</div>';
								}
							} else {
									echo '<div class="input-instructions error">' . $this->message[$subfield['field']]. '</div>';
							}
						} elseif(key_exists('instructions',$subfield)) {
							echo '<div class="input-instructions">' . $subfield['instructions'] . '</div>';
						}
					}
				}

?>
		</div>
	</div>

<?php
						break;
					case 'dropdown':
?>
	<div class="form-row">
		<label class="left"><?php echo (strpos($field['validation'],'required') !== false ? '<strong>' : '') . ($field['label'] ? $field['label'] . ':' : '') . (strpos($field['validation'],'required') !== false ? '</strong>' : ''); ?></label>
		<div class="input-holder raised">
			<select name="<?php echo $field['field']; ?>">
<?php
						if(key_exists('values',$field) && is_array($field['values'])) {
							echo '<option value="" disabled="disabled"' . (is_null($field['default']) ? ' selected="selected"' : '') . '>Please select...</option>' . "\n";
							foreach($field['values'] as $value => $label) {
								echo '<option value="' . $value . '"' . ($field['default'] == $value ? ' selected="selected"' : '') . '>' . $label . '</label>' . "\n";
							}
						}
?>
			</select>
		</div>
	</div>
<?php
						break;
				}
				$first_field = false;
			}
			if($this->nonceName) {
				wp_nonce_field($this->nonceName);
			}
?>
	<div class="form-row">
		<label class="left"></label>
		<div class="input-holder">
<?php
			foreach($this->buttonArr as $button) {
				echo '<button class="button' . (key_exists('class',$button) ? ' ' . $button['class'] : '') . '">' . $button['label'] . (key_exists('fa-icon',$button) ? ' <i class="fa ' . $button['fa-icon'] . '"></i>' : '') . '</button>';
			}
			foreach($this->linkArr as $link) {
				if($link['target'] == 'submit') {
					echo '<a href="#" onclick="preventDefault();form.submit();">' . $link['label'] . '</a>';
				} else {
					echo '<a href="' . $link['target'] . '">' . $link['label'] . '</a>';
				}
			}
?>
		</div>
	</div>
</form>
<?php
	}
	public function returnForm() {
		ob_start();
		$this->buildForm();
		return ob_get_clean();
	}
	public static function validate($post,$fields) {
		$error = array();
		foreach($fields as $field) {
			if(key_exists('field',$field) && key_exists('validation',$field)) {
				$post[$field['field']] = trim($post[$field['field']]);
				if(strpos($field['validation'],'required') !== false) {
					if(empty($post[$field['field']])) {
						$error[$field['field']][] = $field['label'] . ' is a required field.' . '<br />' . "\n";
					}
				}
				if(strpos($field['validation'],'password') !== false) {
					$password_error = self::is_not_strong_password($post[$field['field']]);
					if($password_error) {
						$error[$field['field']][] = $password_error . '<br />' . "\n";
					}
				}
				if(key_exists('must_match',$field)) {
					if($post[$field['field']] != $post[$field['must_match']]) {
						$error[$field['field']][] = $field['must_match_error_msg'] . '<br />' . "\n";
					}
				}
			}
		}
		return $error;
	}
	public static function is_not_strong_password($password) {
		$specialChars = preg_match('@[^\w]@', $password);
		$uppercase = preg_match('@[A-Z]@', $password);
		$lowercase = preg_match('@[a-z]@', $password);
		$number    = preg_match('@[0-9]@', $password);
		$space		 = preg_match('@[\s]@', $password);

		if($uppercase && $lowercase && ($number || $specialChars) && strlen($password) >= 8 && !$space) {
			return false;
		} else {
			return 'Your password should be at least 8 characters long and contain at least 1 lowercase, 1 uppercase, and 1 number or special character. It cannot contain any spaces.';
		}
	}
	public static function custom_redirect($post,$notice=null,$step=null,$action=null) {
		$location = (empty($post['_wp_http_referer']) ? '/' : $post['_wp_http_referer']);
		$nonce = (empty($post['_wpnonce']) ? 'B7U4tzdnYFdRGQEWMML' : $post['_wpnonce']);
		set_transient('svi_transient_message_' . $nonce, $notice, 28800);
		set_transient('svi_transient_post_' . $nonce, $post, 28800);
		$get_vars = array();
		$get_vars['nonce'] = $nonce;
		if($step) {
			$get_vars['step'] = $step;
		}
		if($action) {
			$get_vars['action'] = $action;
		}
		wp_redirect(esc_url_raw(add_query_arg($get_vars, $location)));
		exit();
	}
}