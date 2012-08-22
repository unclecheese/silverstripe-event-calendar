// A lookup for all the various fieldgroups that need to be exposed
var recursion_type_div = 'CustomRecursionType';
var recursion_exceptions_div = 'Form_EditForm_Exceptions_Wrapper';	
var recursion_interval_divs = new Array(
	'Form_EditForm_DailyInterval_Wrapper',
	'Form_EditForm_WeeklyInterval_Wrapper',
	'Form_EditForm_MonthlyInterval_Wrapper'				
);

var open_recursion_interval;

function reset(target) {
	// check to see if we did not get an array. if so, make it one.
	if (target.constructor.toString().indexOf("Array") == -1)
		divs = new Array(target);
	else
		divs = target;
		
	divs.each(function(elem) {
		$$('#'+elem+' input').each(function(item) {
			switch(item.type) {
				case 'checkbox':
					item.checked=false;
					Element.removeClassName(item.parentNode,'selected');
				case 'radio':
					item.checked=false;
				break;
				
				case 'text':
					item.value='';
				break;
			}
		});
		$$('#'+elem+' select').each(function(item) {
			item.selectedIndex = 0;
		});
	})
}

function setDisabled(target, bool) {
	// check to see if we did not get an array. if so, make it one.
	if (target.constructor.toString().indexOf("Array") == -1)
		divs = new Array(target);
	else
		divs = target;
	divs.each(function(elem) {
		$$('#'+elem+' input').each(function(item) {
			item.disabled = bool;
		});
		$$('#'+elem+' select').each(function(item) {
			item.disabled = bool;
		});
	});

}

// Not sure how to use inheritence in the Behavior class, so I just made these global.

function doShow(target) {
	Effect.Appear(target, { queue : 'end', duration : .2 } );			
} 
function doHide(target) {
	Effect.Fade(target, { queue : 'front', duration : .2 } );		
}

Behaviour.register({
	'input#Form_EditForm_Recursion' : {
		initialize : function() {
			Element.hide(recursion_type_div);
			Element.hide($('Repeat_Alert_Message'));
			this.onclick();
		},
		
		onclick : function() {
			div = $('Form_EditForm_DateTimes');
			if(this.checked) {			
				if(Element.hasClassName(div,'DataObjectManager')) {
					complex = true;
					valid_rows = $$('#Form_EditForm_DateTimes_Wrapper ul li.data');								
				}
				else if(Element.hasClassName(div,'ComplexTableField')) {
					complex = true;
					valid_rows = $$('#Form_EditForm_DateTimes_Wrapper table tbody tr');				
				}
				else {
					complex = false;
					table_rows = $$('#Form_EditForm_DateTimes_Wrapper table tbody tr.row');
				}
				
				if(!complex) {
					valid_rows = new Array();
					empty_rows = new Array();
					table_rows.each(function(row){
						inputs = $$('#'+row.id+' td.tablecolumn input');
						data = false;
						inputs.each(function(input) {
							if(input.type == 'text') {
								if(input.value != '')
									data = true;
							}
						});
						if(data) {valid_rows.push(row);}
						else {empty_rows.push(row);}
					});
				}
				if(valid_rows.length != 1) {
					if(valid_rows.length > 1)
						$('Repeat_Alert_Message').innerHTML = 'To repeat an event, you may only have one date. Please some dates and try again.';
					else if(valid_rows.length == 0)
						$('Repeat_Alert_Message').innerHTML = 'To repeat an event, you must specify a date first.';

					Element.show('Repeat_Alert_Message');
					this.checked = false;
				}
				else {
					if(!complex && empty_rows.length) {
						empty_rows.each(function(row) {
							Element.hide(row);
						});
					}
					if(!Element.hasClassName(div,'DataObjectManager'))
						$$('#Form_EditForm_DateTimes_Wrapper table tfoot').each(function(elem) {Element.hide(elem);});

					Element.hide('Repeat_Alert_Message');
					doShow(recursion_type_div);
				}
			}
			else {
				if(Element.hasClassName(div,'DataObjectManager'))
					$$('#Form_EditForm_DateTimes_Wrapper ul li.data').each(function(elem) {Element.show(elem);});
				else 
					$$('#Form_EditForm_DateTimes_Wrapper table tfoot').each(function(elem) {Element.show(elem);});
				doHide(recursion_type_div);
				doHide(recursion_exceptions_div);
				recursion_interval_divs.each(function(elem){
					Element.hide(elem);
				});
				reset(recursion_type_div);
				reset(recursion_interval_divs);
			}
		}
	},
	
	'ul#Form_EditForm_CustomRecursionType li input' : {
		initialize : function() {
			recursion_interval_divs.each(function(elem){Element.hide(elem);});
			doHide(recursion_exceptions_div);
			if(this.checked)
				this.show();
		},
		
		onclick : function() {
			reset(recursion_interval_divs);
			setDisabled('MonthlyIndexMonthlyDayOfWeek',true);
			setDisabled('RecurringDaysOfMonth',true);

			if(this.checked) {
				if(open_recursion_interval) {
					this.hide();
				}
				this.show();
			}
		},
		
		show : function() {
			target = recursion_interval_divs[this.value-1];
			doShow(target);
			open_recursion_interval = target;
			doShow(recursion_exceptions_div);			
		},
		
		hide : function() {	
			doHide(open_recursion_interval);
		}	
	},
	
	'#Form_EditForm_MonthlyRecursionType1_1' : {
		initialize : function() {
			this.onclick();		
		},
		
		onclick : function() {
			//setDisabled('RecurringDaysOfMonth', !this.checked);
			if(this.checked) {
				$('Form_EditForm_MonthlyRecursionType2_1').checked = false;
				reset('MonthlyIndexMonthlyDayOfWeek');
				setDisabled('MonthlyIndexMonthlyDayOfWeek', true);
				setDisabled('RecurringDaysOfMonth', false);
			}
		}
	},
	'#Form_EditForm_MonthlyRecursionType2_1' : {
		initialize : function() {
			this.onclick();		
		},
		
		onclick : function() {
			//setDisabled('MonthlyIndexMonthlyDayOfWeek', !this.checked);
			if(this.checked) {
				$('Form_EditForm_MonthlyRecursionType1_1').checked = false;
				reset('RecurringDaysOfMonth');
				setDisabled('RecurringDaysOfMonth', true);
				setDisabled('MonthlyIndexMonthlyDayOfWeek',false);
			}
		}	
	},
	
	'.TableField input.checkbox' : {
		initialize : function() {
			this.onclick();
		},
		
		onclick : function() {
			checked = this.checked;
			tr = this.parentNode.parentNode;
			$$('tr#'+tr.id+' td input').each(function(elem){
				if(elem.id.match('StartTime') || elem.id.match('EndTime'))
				{
					elem.disabled = checked;
					if(checked)
						elem.value ='';
				}
			});			
		}
	
	},
	
	'#Form_EditForm .checkboxset' : {
		initialize : function() {
			$$('#'+this.id+' ul.optionset li').each(function(elem,index) {
				Element.addClassName(elem,'b-top');
				Element.addClassName(elem,'b-lft');
				if(index % 7 == 6)
					Element.addClassName(elem,'b-rgt');
				if(index > 20) 
					Element.addClassName(elem,'b-btm');
				if(index == 28 || index == 29)
					Element.removeClassName(elem,'b-top');
				if(index == 29)
					Element.addClassName(elem,'b-rgt');
			});
		}
	},
	'#Form_EditForm .checkboxset ul li input' : {
		initialize : function() {
			this.onclick();
		},
		
		onclick : function() {
			if(this.checked)
				Element.addClassName(this.parentNode,'selected');
			else
				Element.removeClassName(this.parentNode,'selected');
		}
	}
});