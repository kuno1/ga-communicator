/*!
 * Custom dimensions.
 *
 * @package ga-communicator
 * @handle ga-custom-dimensions
 * @deps wp-api-fetch, wp-element, wp-components, wp-i18n
 */

const { apiFetch } = wp;
const { render, Component } = wp.element;
const { Spinner } = wp.components;
const { __ } = wp.i18n;

class GaCustomDimensions extends Component {

	constructor( props ) {
		super( props );
		this.state = {
			loading: true,
			error: '',
			dimensions: [],
		};
	}

	componentDidMount() {
		apiFetch( {
			path: 'ga/v1/dimensions',
		} ).then( ( res ) => {
			this.setState( {
				dimensions: res,
			} );
		} ).catch( ( res ) => {
			this.setState( {
				error: res.message,
			} );
		} ).finally( () => {
			this.setState( {
				loading: false,
			} );
		} );
	}

	render() {
		const { loading, error, dimensions } = this.state;
		return (
			<>
				{ loading ? (
					<Spinner />
				) : (
					<>
						<p><strong>{ __( 'Registered Dimension', 'ga-communicator' ) }</strong></p>
						{ ( 0 < error.length ) && (
							<div className="wp-ui-notification">{ error }</div>
						) }
						<ol>
							{ dimensions.map( ( dimension, index ) => {
								return (
									<li key={ `dimension-${index}` }>
										<strong>{ dimension.name }</strong>
										<code style={ { margin: '0 10px' } }>{ dimension.id }</code>
										<span>{ __( 'Scope', 'ga-communicator' ) }: { dimension.scope }</span>
									</li>
								);
							} ) }
						</ol>
					</>
				) }
			</>
		);
	}
}

const div = document.getElementById( 'ga-dimensions' );
render( <GaCustomDimensions />, div );
