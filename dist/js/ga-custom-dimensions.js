/*!
 * Custom dimensions.
 *
 * @deprecated 3.6.0
 * @package ga-communicator
 * @handle ga-custom-dimensions
 * @deps wp-api-fetch, wp-element, wp-components, wp-i18n
 */const{apiFetch}=wp,{render,Component}=wp.element,{Spinner}=wp.components,{__}=wp.i18n;class GaCustomDimensions extends Component{constructor(e){super(e),this.state={loading:!0,error:"",dimensions:[]}}componentDidMount(){apiFetch({path:"ga/v1/dimensions"}).then(e=>{this.setState({dimensions:e})}).catch(e=>{this.setState({error:e.message})}).finally(()=>{this.setState({loading:!1})})}render(){const{loading:e,error:t,dimensions:s}=this.state;return wp.element.createElement(wp.element.Fragment,null,e?wp.element.createElement(Spinner,null):wp.element.createElement(wp.element.Fragment,null,wp.element.createElement("p",null,wp.element.createElement("strong",null,__("Registered Dimension","ga-communicator"))),0<t.length&&wp.element.createElement("div",{className:"wp-ui-notification"},t),wp.element.createElement("ol",null,s.map((n,o)=>wp.element.createElement("li",{key:`dimension-${o}`},wp.element.createElement("strong",null,n.name),wp.element.createElement("code",{style:{margin:"0 10px"}},n.id),wp.element.createElement("span",null,__("Scope","ga-communicator"),": ",n.scope))))))}}const div=document.getElementById("ga-dimensions");render(wp.element.createElement(GaCustomDimensions,null),div);
//# sourceMappingURL=ga-custom-dimensions.js.map
