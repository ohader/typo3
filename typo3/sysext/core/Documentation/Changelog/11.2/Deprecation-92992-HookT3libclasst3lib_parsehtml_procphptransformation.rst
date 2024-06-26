.. include:: /Includes.rst.txt

.. _deprecation-92992:

==================================================================
Deprecation: #92992 - Hook t3lib_parsehtml_proc.php:transformation
==================================================================

See :issue:`92992`

Description
===========

Since the deprecation of several internal functions in the
:php:`TYPO3\CMS\Core\Html\RteHtmlParser` in TYPO3 10.2 (:ref:`Deprecation:
#86440 - Internal Methods and properties within RteHtmlParser <changelog:deprecation-86440>`)
the hook :php:`t3lib/class.t3lib_parsehtml_proc.php:transformation` became quite useless.

It is therefore marked as deprecated and will be removed with TYPO3 v12.

Impact
======

Calling the hook will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations with extensions installed that implement the hook.


Migration
=========

Migrate to use the public API only and use other options (such as
:php:`allowAttributes`) in order to only run certain instructions on the :php:`RteHtmlParser` object.

.. index:: RTE, NotScanned, ext:core
