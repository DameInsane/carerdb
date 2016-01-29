<?php

class Field {
	public $dbh;
	public $id, $form_id, $slug, $type, $sort_order, $label, $help_text;
	public $default_value, $enumeration, $regex, $minimum, $maximum, $format;
	
	public static function preferred_class ($type) {
		if ($type === 'text')      return 'Field';
		if ($type === 'select')    return 'SelectField';
		if ($type === 'boolean')   return 'BooleanField';
		if ($type === 'number')    return 'NumberField';
		if ($type === 'datetime')  return 'DatetimeField';
		if ($type === 'date')      return 'DateField';
		if ($type === 'time')      return 'TimeField';
		if ($type === 'url')       return 'UrlField';
		if ($type === 'email')     return 'EmailField';
		if ($type === 'tel')       return 'TelField';
		return 'Field';
	}
	
	public static function known_field_types () {
		return array(
			'text'       => 'Text',
			'number'     => 'Numeric',
			'boolean'    => 'Boolean',
			'url'        => 'URL',
			'email'      => 'Email',
			'tel'        => 'Telephone Number',
			'datetime'   => 'Date and Time',
			'date'       => 'Date',
			'time'       => 'Time',
			'select'     => 'Selection'
		);
	}
	
	public function html5_input_type () { return 'text'; }
	
	public static function from_database ($dbh, $row) {
		$cls = self::preferred_class($row['type']);
		$f   = new $cls ();
		
		$f->dbh = $dbh;
		foreach ($row as $k=>$v) {
			$f->$k = $v;
		}
		
		return $f;
	}
	
	public function format_label_html () {
		return htmlspecialchars($this->label);
	}

	public function format_value_html ($value) {
		return htmlspecialchars($value);
	}
	
	public function control_html ($value) {
		$out = sprintf('<div class="form-group" id="control_for_%s">', $this->slug);
		$out .= sprintf('<label for="control_input_for_%s">%s</label>', $this->slug, $this->format_label_html());
		$out .= $this->control_html_compact($value);
		$out .= "</div>\n";
		return $out;
	}
	
	public function control_html_compact ($value) {
		if (is_null($value)) $value = $this->default_value;
		return sprintf(
			'<input class="form-control" id="control_input_for_%s" name="%s" value="%s" type="%s" %s%s/>',
			$this->slug,
			$this->slug,
			htmlspecialchars($value),
			$this->html5_input_type,
			( (empty($this->maximum_length)||$this->maximum_length<=0) ? '' : sprintf('maxlength="%d" ', $this->maximum_length) ),
			( (empty($this->minimum_length)||$this->minimum_length<=0) ? '' : 'required="required"' )
		);
	}

	public function gather_data ($data) {
		return $data[ $this->slug ];
	}

	public function validate_data ($data) {
		$value = $this->gather_data($data);
		
		if (!empty($this->minimum)) {
			if ($value < $this->minimum) {
				return sprintf(
					'%s: "%s" below minimum "%s".',
					htmlspecialchars($this->label),
					htmlspecialchars($value),
					htmlspecialchars($this->minimum)
				);
			}
		}
		
		if (!empty($this->maximum)) {
			if ($value > $this->maximum) {
				return sprintf(
					'%s: "%s" above maximum "%s".',
					htmlspecialchars($this->label),
					htmlspecialchars($value),
					htmlspecialchars($this->maximum)
				);
			}
		}
		
		if (!empty($this->minimum_length)) {
			if (strlen($value) < $this->minimum_length) {
				return sprintf(
					'%s: "%s" shorter than minimum length %d.',
					htmlspecialchars($this->label),
					htmlspecialchars($value),
					$this->minimum_length
				);
			}
		}
		
		if (!empty($this->maximum_length)) {
			if ($this->maximum_length > 0 && strlen($value) > $this->maximum_length) {
				return sprintf(
					'%s: "%s" longer than maximum length %d.',
					htmlspecialchars($this->label),
					htmlspecialchars($value),
					$this->maximum_length
				);
			}
		}
		
		if (!empty($this->regex)) {
			if (!preg_match($this->regex,$value)) {
				return sprintf(
					'%s: "%s" is not a valid value.',
					htmlspecialchars($this->label),
					htmlspecialchars($value)
				);
			}
		}
		
		if (!empty($this->enumeration)) {
			$enum = explode('|', $this->enumeration);
			if (!in_array($value, $enum)) {
				return sprintf(
					'%s: "%s" is not a valid value ().',
					htmlspecialchars($this->label),
					htmlspecialchars($value),
					htmlspecialchars($this->enumeration)
				);
			}
		}
	}
}

class SelectField extends Field {
	public function control_html_compact ($value) {
		if (is_null($value)) $value = $this->default_value;
		$out = sprintf('<select class="form-control" id="control_input_for_%s" name="%s">', $this->slug, $this->slug);
		foreach (explode('|',$this->enumeration) as $o) {
			$out .= sprintf('<option%s>%s</option>', ($o===$value)?' selected="selected"':'', htmlspecialchars($o));
		}
		$out .= '</select>';
		return $out;
	}
}

class NumberField extends Field {
	public function control_html_compact ($value) {
		if (is_null($value)) $value = $this->default_value;
		return sprintf(
			'<input class="form-control" id="control_input_for_%s" name="%s" value="%s" type="number" %s %s />',
			$this->slug,
			$this->slug,
			htmlspecialchars($value),
			is_null($this->minimum) ? '' : sprintf('min="%s"', htmlspecialchars($this->minimum)),
			is_null($this->maximum) ? '' : sprintf('min="%s"', htmlspecialchars($this->maximum))
		);
	}
}

class DatetimeField extends Field {
	public function format_value_html ($value) {
		$date = strtotime($value);
		return sprintf('<time datetime="%s" title="%s" class="nowrap">%s</time>', date('c', $date), date('r', $date), date('d/m H:i', $date));
	}
	public function control_html_compact ($value) {
		if (is_null($value)) $value = $this->default_value;
		return sprintf('<input class="form-control datetimepicker" id="control_input_for_%s" name="%s" value="%s" type="text" />', $this->slug, $this->slug, htmlspecialchars($value));
	}
}

class DateField extends Field {
	public function format_value_html ($value) {
		$date = strtotime($value);
		return sprintf('<time datetime="%s" title="%s" class="nowrap">%s</time>', date('c', $date), date('r', $date), date('d/m/Y', $date));
	}
	public function control_html_compact ($value) {
		if (is_null($value)) $value = $this->default_value;
		return sprintf('<input class="form-control datetimepicker" size="12" data-datetime-format="YYYY-MM-DD" id="control_input_for_%s" name="%s" value="%s" type="text" />', $this->slug, $this->slug, htmlspecialchars($value));
	}
}

class TimeField extends Field {
	public function control_html_compact ($value) {
		if (is_null($value)) $value = $this->default_value;
		return sprintf('<input class="form-control datetimepicker" size="6" data-datetime-format="HH:mm" id="control_input_for_%s" name="%s" value="%s" type="text" />', $this->slug, $this->slug, htmlspecialchars($value));
	}
}

class BooleanField extends Field {
	public function format_value_html ($value) {
		return ($value ? '<span class="yes">Y</span>' : '<span class="no">N</span>');
	}
	public function control_html_compact ($value) {
		if (is_null($value)) $value = $this->default_value;
		return sprintf('<input id="control_input_for_%s" name="%s" value="1" %s type="checkbox">', $this->slug, $this->slug, $value ? 'checked="checked"' : '');
	}
}

class UrlField extends Field {
	public function html5_input_type () { return 'url'; }	
}

class EmailField extends Field {
	public function html5_input_type () { return 'url'; }	
}

class TelField extends Field {
	public function html5_input_type () { return 'url'; }	
}
