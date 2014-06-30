Ext.ns('TYPO3.Workspaces.Component');

TYPO3.Workspaces.Component.CheckboxSelectionModel = Ext.extend(Ext.grid.CheckboxSelectionModel, {
	threshold: 400,
	mouseDownStatus: {},

	constructor: function() {
		this.addEvents(
			'holdclick'
		);

		TYPO3.Workspaces.Component.CheckboxSelectionModel.superclass.constructor.apply(this, arguments);
	 },

	initEvents: function() {
		TYPO3.Workspaces.Component.CheckboxSelectionModel.superclass.initEvents.call(this);
		this.grid.on('render', function() {
			this.grid.on('rowmouseup', this.handleMouseUp, this);
		}, this);
	},

	processEvent: function(name, e, grid, rowIndex, colIndex) {
		if (name === 'mousedown') {
			this.onMouseDown(e, e.getTarget());
			return false;
		} else if (name === 'mouseup') {
			this.onMouseUp(e, e.getTarget());
			return false;
		} else {
			return TYPO3.Workspaces.Component.CheckboxSelectionModel.superclass.processEvent.apply(this, arguments);
		}
	},

	handleMouseDown: function(g, rowIndex, e) {
		e.stopEvent();
		if (this.isSelected(rowIndex)) {
			this.deselectRow(rowIndex);
		} else {
			this.selectRow(rowIndex, true);
			this.grid.getView().focusRow(rowIndex);
		}
	},

	handleMouseUp: function(g, rowIndex, e){
	},

	onMouseDown: function(e, t) {
		if (this.mouseDownStatus.timeout) {
			clearTimeout(this.mouseDownStatus.timeout);
		}
		this.mouseDownStatus = {
			target: t,
			timeout: Ext.defer(this.handleHoldClick, this.threshold, this, [e, t])
		};
		TYPO3.Workspaces.Component.CheckboxSelectionModel.superclass.onMouseDown.apply(this, arguments);
	},

	handleHoldClick: function(e, t) {
		var row = e.getTarget('.x-grid3-row');
		if (row) {
			this.fireEvent('holdclick', this, row.rowIndex, this.grid.store.getAt(row.rowIndex));
		}
		this.clearMouseDownStatus();
	},

	onMouseUp: function(e, t) {
		this.clearMouseDownStatus();
	},

	onHoldClick: function(e, t) {

	},

	clearMouseDownStatus: function() {
		if (this.mouseDownStatus.timeout) {
			clearTimeout(this.mouseDownStatus.timeout);
		}
		this.mouseDownStatus = {};
	}
});