/*
	Copyright 2014 Mhd Sulhan
	Authors:
		- mhd.sulhan (m.shulhan@gmail.com)
*/
function JxLogin ()
{
	this.id				= "Login";

	this.logo			= Ext.create ("Ext.Component", {
			id			:this.id +"_logo"
		,	html		:"<img src='"
							+ Jx.generateModDir ("System_Profile_Logo")
							+"read.php?_profile_id=1' />"
		});

	this.username		= Ext.create ("Ext.form.field.Text", {
			fieldLabel	:"Username"
		,	itemId		:"username"
		,	name		:"username"
		,	allowBlank	:false
		});

	this.password		= Ext.create ("Ext.form.field.Text", {
			fieldLabel	:"Password"
		,	itemId		:"password"
		,	name		:"password"
		,	inputType	:"password"
		,	allowBlank	:false
		,	listeners	:
			{
				scope		:this
			,	specialkey	:function (f, e)
				{
					if (e.ENTER === e.getKey ()) {
						if (this.panel.isValid ()) {
							this.doLogin ();
						}
					}
				}
			}
		});

	this.buttonLogin	= Ext.create ("Ext.button.Button", {
			text		:"Log In"
		,	itemId		:"login"
		,	iconCls		:"login"
		,	formBind	:true
		,	scope		:this
		,	handler		:function (b)
			{
				this.doLogin ();
			}
		});

	this.panel				= Ext.create ("Jx.Form", {
			id				:this.id +"Form"
		,	url				:Jx.generateModDir ("login")
		,	createButtonBar	:false
		,	syncUseStore	:false
		,	defaults		:
			{
				labelStyle		:"font-weight:bold"
			,	vtype			:"alphanum"
			}
		,	items			:
			[
				this.username
			,	this.password
			]
		,	layout			:'vbox'
		,	layoutConfig	:
			{
				padding		:'5'
			,	pack		:'center'
			,	align		:'middle'
			}
		,	buttons			:
			[
				this.buttonLogin
			]
		});

	this.win		= Ext.create ("Ext.window.Window", {
		id			:this.id
	,	title		:_g_title
	,	titleAlign	:"center"
	,	autoShow	:true
	,	draggable	:false
	,	closable	:false
	,	resizable	:false
	,	shadow		:false
	,	defaultFocus:"username"
	,	items		:
		[
			this.logo
		,	this.panel
		]
	});

	this.doLogin = function ()
	{
		Jx.showMask ();
		this.panel.submit ({
			success	:function (form, action)
			{
				Jx.hideMask ();
				location.href = _g_root;
			}
		,	failure	:function (form, action)
			{
				Jx.hideMask ();
				Jx.msg.error (action.result.data);
			}
		});
	};
}

Ext.onReady (function ()
{
	var loginwin = new JxLogin ();
});

//# sourceURL=module/home/layout.js
