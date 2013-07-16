<section id="delete" title="Delete">
  <div class="page-header">
    <h1>Delete</h1>
  </div>

    <p class="lead">Delete a document.</p>

    <p>The response from a successful DELETE will always be a 204.</p>

    <h2>Delete a document</h2>

<pre class="prettyprint linenums">
DELETE
HEADERS
Content-type: application/json

http://myserver.com/rest/user/toby
</pre>

    <h2>Delete a list</h2>

<pre class="prettyprint linenums">
DELETE
HEADERS
Content-type: application/json

http://myserver.com/rest/user
</pre>

    <h2>Delete single nested document</h2>

    <p>Append the property name:</p>
<pre class="prettyprint linenums">
DELETE
HEADERS
Content-type: application/json

http://myserver.com/rest/user/toby/address
</pre>

    <h2>Delete single document in a nested list</h2>

    <p>To delete one item from a list of documents append the property name and the id of the document you want:</p>
<pre class="prettyprint linenums">
DELETE
HEADERS
Content-type: application/json

http://myserver.com/rest/user/toby/assets/funky-car
</pre>

    <h2>Errors</h2>

<table class="table table-bordered table-striped">
  <thead>
   <tr>
     <th style="width: 100px;">error</th>
     <th>description</th>
   </tr>
  </thead>
  <tbody>
<tr>
    <td>404</td>
    <td>If the endpoint or document requested does not exist.</td>
</tr>
</tbody>
</table>

</section>
