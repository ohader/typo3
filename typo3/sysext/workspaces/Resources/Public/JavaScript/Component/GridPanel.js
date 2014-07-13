Ext.ns('TYPO3.Workspaces.Component');

TYPO3.Workspaces.Component.GridPanel = Ext.extend(Ext.grid.GridPanel, {
	nestingCls: 'typo3-workspaces-collection-nesting',

	onRender: function(ct, position){
		TYPO3.Workspaces.Component.GridPanel.superclass.onRender.apply(this, arguments);

		if (TYPO3.Workspaces.Helpers.getNestRecordsSetting()) {
			this.enableCollectionNesting();
		}

		var c = this.getGridEl();

		this.mon(c, {
			scope: this,
			mouseup: this.onMouseUp
		});
	},

	onMouseUp: function(e) {
		this.processEvent('mouseup', e);
	},

	enableCollectionNesting: function() {
		this.addClass(this.nestingCls);
	},
	disableCollectionNesting: function() {
		this.removeClass(this.nestingCls);
	}
});