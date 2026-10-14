/**
 * Entry point for the Students report chart, built to assets/build/admin.js.
 */
import domReady from '@wordpress/dom-ready';
import { getThemeColor, renderCharts } from './chart';
import './index.css';

domReady( () => renderCharts( document, getThemeColor() ) );
