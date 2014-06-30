Ext.ns('TYPO3.Workspaces.Component');

TYPO3.Workspaces.Component.GridPanel = Ext.extend(Ext.grid.GridPanel, {
	onRender: function(ct, position){
		TYPO3.Workspaces.Component.GridPanel.superclass.onRender.apply(this, arguments);

		var c = this.getGridEl();

		this.mon(c, {
			scope: this,
			mouseup: this.onMouseUp
		});
	},

	onMouseUp: function(e) {
		this.processEvent('mouseup', e);
	}
});