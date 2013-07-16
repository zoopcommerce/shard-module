<section id="post" title="Post">
  <div class="page-header">
    <h1>Post</h1>
  </div>

    <p class="lead">Create a document</p>

    <p>To create a new document, use a POST request, and place the json document in the request body.</p>

<pre class="prettyprint linenums">
POST
HEADERS
Content-type: application/json

CONTENT
{"username": "lucy", "age": 27}
http://myserver.com/rest/user
</pre>

    <h2>Single nested document</h2>
<pre class="prettyprint linenums">
POST
HEADERS
Content-type: application/json

CONTENT
{"street": "Street Rd", "number": "45", city: "Sydney"}
http://myserver.com/rest/user/address
</pre>


    <p>Single document from a nested list</p>
<pre class="prettyprint linenums">
POST
HEADERS
Content-type: application/json

CONTENT
{"make": "ford", "model": "stumpy"}
http://myserver.com/rest/user/assets/old-car
</pre>

    <h2>Create with reference</h2>

    <p>Add a reference to an already existing document.</p>
<pre class="prettyprint linenums">
POST
HEADERS
Content-type: application/json

CONTENT
{"username": "jsason", "country": {"$ref": "country/australia"}}
http://myserver.com/rest/user
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
<tr>
    <td>500</td>
    <td><code>Content-Type: application/api-problem+json</code>, Document validation failed. Occurs if the validation extension is turned on, and validation fails.</td>
</tr>
<tr>
    <td>500</td>
    <td><code>Content-Type: application/api-problem+json</code>, Document already exists. Occurs request to create a document with id that already exists.</td>
</tr>
</tbody>
</table>

</section>
