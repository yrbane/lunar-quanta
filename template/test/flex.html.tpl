[% extends 'base.html.tpl' %]

[% block content %]

  <style>

    body {
      font-family: system-ui, sans-serif;
      background: #f5f7fa;
      color: #222;
    }
    h1, h2 {
      text-align: center;
      margin-top: 2rem;
    }
    .container-table {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
      gap: 2rem;
      margin: 2rem;
    }
    .flexbox-demo, .grid-demo {
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 1em;
      box-shadow: 0 2px 10px #0001;
      padding: 1em;
      margin-bottom: 2em;
      display: flex;
      flex-direction: column;
      align-items: stretch;
    }
    .flexbox-demo h3, .grid-demo h3 {
      font-size: 1.1em;
      margin: 0 0 1em 0;
      text-align: left;
      font-weight: bold;
    }
    .flex-container {
      display: flex;
      flex-wrap: wrap;
      width: 320px;
      min-height: 130px;
      background: #e6edf3;
      border: 1px dashed #aac;
      margin: auto;
      border-radius: 0.5em;
      padding: 0.3em;
      box-sizing: border-box;
      gap: 0.3em;
    }
    .flex-item {
      min-width:25px;
      min-height:25px;
      background: #c1e2f2;
      border-radius: 0.4em;
      margin: 0;
      align-items: center;
      justify-content: center;
      font-size: 0.9em;
      font-weight: 600;
      text-align: center;
    }
    /* Pour les grids */
    .grid-container {
      display: grid;
      grid-template-columns: repeat(4, 44px);
      grid-template-rows: repeat(4, 32px);
      width: 206px;
      min-height: 140px;
      background: #e6edf3;
      border: 1px dashed #aac;
      margin: auto;
      border-radius: 0.5em;
      gap: 0.3em;
      box-sizing: border-box;
      padding: 0.3em;
      justify-items: start;
      align-items: start;
    }
    .grid-item {
      background: #fbe2b9;
      border-radius: 0.4em;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 0.9em;
    }
    .caption {
      font-size: 0.95em;
      color: #666;
      text-align: left;
      margin: 0.5em 0 1em 0;
    }
  </style>

 <h1>Comparatif visuel Flexbox & Grid</h1>
  <h2>justify-content, align-items, align-content, justify-items</h2>
  <div class="container-table">

    <!-- justify-content -->
    <div class="flexbox-demo">
      <h3>justify-content</h3>
      <div class="caption">Ajuste l’alignement horizontal (axe principal) des éléments flex.<br><strong>Propriétés testées :</strong> flex-start, flex-end, center, space-between, space-around, space-evenly, start, end, left, right</div>
      <div class="flex-container" style="justify-content: flex-start;">
        <!-- 16 éléments -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">justify-content: flex-start</div>
      <div class="flex-container" style="justify-content: flex-end;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">justify-content: flex-end</div>
      <div class="flex-container" style="justify-content: center;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">justify-content: center</div>
      <div class="flex-container" style="justify-content: space-between;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">justify-content: space-between</div>
      <div class="flex-container" style="justify-content: space-around;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">justify-content: space-around</div>
      <div class="flex-container" style="justify-content: space-evenly;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">justify-content: space-evenly</div>
      <div class="flex-container" style="justify-content: start;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">justify-content: start</div>
      <div class="flex-container" style="justify-content: end;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">justify-content: end</div>
      <div class="flex-container" style="justify-content: left;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">justify-content: left</div>
      <div class="flex-container" style="justify-content: right;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">justify-content: right</div>
    </div>

    <!-- align-items -->
    <div class="flexbox-demo">
      <h3>align-items</h3>
      <div class="caption">Ajuste l’alignement vertical (axe secondaire) de chaque ligne.<br><strong>Propriétés testées :</strong> stretch, flex-start, flex-end, center, baseline, start, end, self-start, self-end, normal</div>
      <div class="flex-container" style="align-items: stretch; min-height: 150px;">
        <!-- 16 éléments -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-items: stretch (défaut)</div>
      <div class="flex-container" style="align-items: flex-start; min-height: 150px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-items: flex-start</div>
      <div class="flex-container" style="align-items: flex-end; min-height: 150px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-items: flex-end</div>
      <div class="flex-container" style="align-items: center; min-height: 150px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-items: center</div>
      <div class="flex-container" style="align-items: baseline; min-height: 150px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-items: baseline</div>
      <div class="flex-container" style="align-items: start; min-height: 150px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-items: start</div>
      <div class="flex-container" style="align-items: end; min-height: 150px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-items: end</div>
      <div class="flex-container" style="align-items: self-start; min-height: 150px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-items: self-start</div>
      <div class="flex-container" style="align-items: self-end; min-height: 150px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-items: self-end</div>
      <div class="flex-container" style="align-items: normal; min-height: 150px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-items: normal</div>
    </div>

    <!-- align-content -->
    <div class="flexbox-demo">
      <h3>align-content</h3>
      <div class="caption">Aligne toutes les lignes sur l’axe secondaire (si plusieurs lignes).<br><strong>Propriétés testées :</strong> stretch, flex-start, flex-end, center, space-between, space-around, space-evenly, start, end, baseline, normal</div>
      <div class="flex-container" style="align-content: stretch; min-height: 200px;">
        <!-- 16 éléments -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-content: stretch (défaut)</div>
      <div class="flex-container" style="align-content: flex-start; min-height: 200px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-content: flex-start</div>
      <div class="flex-container" style="align-content: flex-end; min-height: 200px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-content: flex-end</div>
      <div class="flex-container" style="align-content: center; min-height: 200px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-content: center</div>
      <div class="flex-container" style="align-content: space-between; min-height: 200px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-content: space-between</div>
      <div class="flex-container" style="align-content: space-around; min-height: 200px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-content: space-around</div>
      <div class="flex-container" style="align-content: space-evenly; min-height: 200px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-content: space-evenly</div>
      <div class="flex-container" style="align-content: start; min-height: 200px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-content: start</div>
      <div class="flex-container" style="align-content: end; min-height: 200px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-content: end</div>
      <div class="flex-container" style="align-content: baseline; min-height: 200px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-content: baseline</div>
      <div class="flex-container" style="align-content: normal; min-height: 200px;">
        <!-- idem -->
        <div class="flex-item">1</div><div class="flex-item">2</div><div class="flex-item">3</div><div class="flex-item">4</div>
        <div class="flex-item">5</div><div class="flex-item">6</div><div class="flex-item">7</div><div class="flex-item">8</div>
        <div class="flex-item">9</div><div class="flex-item">10</div><div class="flex-item">11</div><div class="flex-item">12</div>
        <div class="flex-item">13</div><div class="flex-item">14</div><div class="flex-item">15</div><div class="flex-item">16</div>
      </div>
      <div class="caption">align-content: normal</div>
    </div>

    <!-- justify-items (GRID) -->
    <div class="grid-demo">
      <h3>justify-items (Grid)</h3>
      <div class="caption">Ajuste l’alignement horizontal des cellules (ne fonctionne que sur CSS Grid, pas Flexbox).<br><strong>Propriétés testées :</strong> stretch, start, end, center, left, right, legacy, normal</div>
      <div class="grid-container" style="justify-items: stretch;">
        <!-- 16 éléments -->
        <div class="grid-item">1</div><div class="grid-item">2</div><div class="grid-item">3</div><div class="grid-item">4</div>
        <div class="grid-item">5</div><div class="grid-item">6</div><div class="grid-item">7</div><div class="grid-item">8</div>
        <div class="grid-item">9</div><div class="grid-item">10</div><div class="grid-item">11</div><div class="grid-item">12</div>
        <div class="grid-item">13</div><div class="grid-item">14</div><div class="grid-item">15</div><div class="grid-item">16</div>
      </div>
      <div class="caption">justify-items: stretch (défaut)</div>
      <div class="grid-container" style="justify-items: start;">
        <!-- idem -->
        <div class="grid-item">1</div><div class="grid-item">2</div><div class="grid-item">3</div><div class="grid-item">4</div>
        <div class="grid-item">5</div><div class="grid-item">6</div><div class="grid-item">7</div><div class="grid-item">8</div>
        <div class="grid-item">9</div><div class="grid-item">10</div><div class="grid-item">11</div><div class="grid-item">12</div>
        <div class="grid-item">13</div><div class="grid-item">14</div><div class="grid-item">15</div><div class="grid-item">16</div>
      </div>
      <div class="caption">justify-items: start</div>
      <div class="grid-container" style="justify-items: end;">
        <!-- idem -->
        <div class="grid-item">1</div><div class="grid-item">2</div><div class="grid-item">3</div><div class="grid-item">4</div>
        <div class="grid-item">5</div><div class="grid-item">6</div><div class="grid-item">7</div><div class="grid-item">8</div>
        <div class="grid-item">9</div><div class="grid-item">10</div><div class="grid-item">11</div><div class="grid-item">12</div>
        <div class="grid-item">13</div><div class="grid-item">14</div><div class="grid-item">15</div><div class="grid-item">16</div>
      </div>
      <div class="caption">justify-items: end</div>
      <div class="grid-container" style="justify-items: center;">
        <!-- idem -->
        <div class="grid-item">1</div><div class="grid-item">2</div><div class="grid-item">3</div><div class="grid-item">4</div>
        <div class="grid-item">5</div><div class="grid-item">6</div><div class="grid-item">7</div><div class="grid-item">8</div>
        <div class="grid-item">9</div><div class="grid-item">10</div><div class="grid-item">11</div><div class="grid-item">12</div>
        <div class="grid-item">13</div><div class="grid-item">14</div><div class="grid-item">15</div><div class="grid-item">16</div>
      </div>
      <div class="caption">justify-items: center</div>
      <div class="grid-container" style="justify-items: left;">
        <!-- idem -->
        <div class="grid-item">1</div><div class="grid-item">2</div><div class="grid-item">3</div><div class="grid-item">4</div>
        <div class="grid-item">5</div><div class="grid-item">6</div><div class="grid-item">7</div><div class="grid-item">8</div>
        <div class="grid-item">9</div><div class="grid-item">10</div><div class="grid-item">11</div><div class="grid-item">12</div>
        <div class="grid-item">13</div><div class="grid-item">14</div><div class="grid-item">15</div><div class="grid-item">16</div>
      </div>
      <div class="caption">justify-items: left</div>
      <div class="grid-container" style="justify-items: right;">
        <!-- idem -->
        <div class="grid-item">1</div><div class="grid-item">2</div><div class="grid-item">3</div><div class="grid-item">4</div>
        <div class="grid-item">5</div><div class="grid-item">6</div><div class="grid-item">7</div><div class="grid-item">8</div>
        <div class="grid-item">9</div><div class="grid-item">10</div><div class="grid-item">11</div><div class="grid-item">12</div>
        <div class="grid-item">13</div><div class="grid-item">14</div><div class="grid-item">15</div><div class="grid-item">16</div>
      </div>
      <div class="caption">justify-items: right</div>
      <div class="grid-container" style="justify-items: legacy;">
        <!-- idem -->
        <div class="grid-item">1</div><div class="grid-item">2</div><div class="grid-item">3</div><div class="grid-item">4</div>
        <div class="grid-item">5</div><div class="grid-item">6</div><div class="grid-item">7</div><div class="grid-item">8</div>
        <div class="grid-item">9</div><div class="grid-item">10</div><div class="grid-item">11</div><div class="grid-item">12</div>
        <div class="grid-item">13</div><div class="grid-item">14</div><div class="grid-item">15</div><div class="grid-item">16</div>
      </div>
      <div class="caption">justify-items: legacy</div>
      <div class="grid-container" style="justify-items: normal;">
        <!-- idem -->
        <div class="grid-item">1</div><div class="grid-item">2</div><div class="grid-item">3</div><div class="grid-item">4</div>
        <div class="grid-item">5</div><div class="grid-item">6</div><div class="grid-item">7</div><div class="grid-item">8</div>
        <div class="grid-item">9</div><div class="grid-item">10</div><div class="grid-item">11</div><div class="grid-item">12</div>
        <div class="grid-item">13</div><div class="grid-item">14</div><div class="grid-item">15</div><div class="grid-item">16</div>
      </div>
      <div class="caption">justify-items: normal</div>
    </div>
  </div>
[% endblock %]