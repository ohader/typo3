Ext.ns('TYPO3.Workspaces.Component');

TYPO3.Workspaces.Component.GroupingView = Ext.extend(Ext.grid.GroupingView, {
	constructor: function(){
		this.addEvents(
			'rowover',
			'rowout'
		);

		TYPO3.Workspaces.Component.GroupingView.superclass.constructor.apply(this, arguments);
	 },

	onRowOver: function(e, target) {
		TYPO3.Workspaces.Component.GroupingView.superclass.onRowOver.apply(this, arguments);
		var rowIndex = this.findRowIndex(target);
		if (typeof rowIndex === 'number') {
			this.fireEvent('rowover', this, rowIndex);
		}
	},

	onRowOut: function(e, target) {
		TYPO3.Workspaces.Component.GroupingView.superclass.onRowOut.apply(this, arguments);
		var rowIndex = this.findRowIndex(target);
		if (typeof rowIndex === 'number') {
			this.fireEvent('rowout', this, rowIndex);
		}
	}
});