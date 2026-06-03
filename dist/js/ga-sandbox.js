/*!
 * Sandbox
 *
 * @package ga-communicator
 * @handle ga-sandbox
 * @deps wp-codemirror, wp-api-fetch
 */const{CodeMirror,apiFetch}=wp,editor=CodeMirror.fromTextArea(document.getElementById("ga-sandbox-inner"),{mode:"application/json",matchBrackets:!0,autoCloseBrackets:!0,lineWrapping:!0}),pushError=(s,t="success")=>{const e=document.getElementById("ga-sandbox-result");["success","busy","error"].forEach(a=>{a===t?e.classList.add(a):e.classList.remove(a)}),e.value=s},button=document.getElementById("ga-sandbox-exec");document.getElementById("ga-sandbox-exec").addEventListener("click",s=>{s.preventDefault();const t=editor.getValue();try{JSON.parse(t),pushError("","busy"),button.classList.add("is-busy"),apiFetch({path:"ga/v1/batch",method:"post",data:{data:t}}).then(e=>{pushError(JSON.stringify(e,null,2),"success")}).catch(e=>{pushError(e.message,"error")}).finally(()=>{button.classList.remove("is-busy")})}catch(e){pushError(e,"error")}});
//# sourceMappingURL=ga-sandbox.js.map
