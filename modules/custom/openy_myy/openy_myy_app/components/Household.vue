<template>
  <section class="myy-household">
    <div class="row headline">
      <div class="col">
        <h3>Household</h3>
      </div>
    </div>
    <div class="row content">
      <div class="col">
        <div class="row">
          <div class="col">
            <span class="rounded_letter green">J</span>
            <div class="name">Me (John Doe)</div>
            <div class="age">36</div>
            <div class="dropdown">
              <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Select
              </button>
              <div class="dropdown-menu" aria-labelledby="dropdownMenu1">
                <button class="dropdown-item" type="button">Action</button>
                <button class="dropdown-item" type="button">Another action</button>
                <button class="dropdown-item" type="button">Something else here</button>
              </div>
            </div>
          </div>
          <div class="col">
            <span class="rounded_letter red">J</span>
            <div class="name">Janne Doe</div>
            <div class="age">36 (Primary)</div>
            <div class="dropdown">
              <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Select
              </button>
              <div class="dropdown-menu" aria-labelledby="dropdownMenu1">
                <button class="dropdown-item" type="button">Action</button>
                <button class="dropdown-item" type="button">Another action</button>
                <button class="dropdown-item" type="button">Something else here</button>
              </div>
            </div>
          </div>
          <div class="col">
            <span class="rounded_letter blue">B</span>
            <div class="name">Buck Doe</div>
            <div class="age">36</div>
            <div class="dropdown">
              <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Select
              </button>
              <div class="dropdown-menu" aria-labelledby="dropdownMenu1">
                <button class="dropdown-item" type="button">Action</button>
                <button class="dropdown-item" type="button">Another action</button>
                <button class="dropdown-item" type="button">Something else here</button>
              </div>
            </div>
          </div>
          <div class="col">
            <span class="rounded_letter purple">D</span>
            <div class="name">Daedra Doe</div>
            <div class="age">36</div>
            <div class="dropdown">
              <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Select
              </button>
              <div class="dropdown-menu" aria-labelledby="dropdownMenu1">
                <button class="dropdown-item" type="button">Action</button>
                <button class="dropdown-item" type="button">Another action</button>
                <button class="dropdown-item" type="button">Something else here</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style lang="scss">
  .myy-household {
    line-height: 17px;
    margin-bottom: 40px;
    .headline {
      padding: 20px 5px;
      margin: 0;
      border: 1px solid #636466;
    }
    h3 {
      font-size: 14px;
      text-transform: uppercase;
      line-height: 17px;
      color: #636466;
      font-weight: bold;
      margin: 0;
    }
    .content {
      border-left: 1px solid #636466;
      border-right: 1px solid #636466;
      border-bottom: 1px solid #636466;
      margin: 0;
      padding: 20px 5px;
      .col {
        text-align: center;
      }
      .rounded_letter {
        margin-bottom: 20px;
      }
      .name {
        color: #231F20;
        font-weight: bold;
        line-height: 21px;
      }
      .btn {
        background-color: #92278F;
        border-color: #92278F;
        text-align: left;
        font-size: 14px;
        margin-top: 10px;
        width: 100%;
        &:after {
          position: absolute;
          right: 10px;
          top: 25px;
        }
      }
    }
  }
</style>

