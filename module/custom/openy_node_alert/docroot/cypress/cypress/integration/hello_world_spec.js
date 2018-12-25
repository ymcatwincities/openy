describe('My First Test', function() {
  it('Does not do much!', function() {
    cy.visit("locations", {
      auth: {
        username: 'ygtc',
        password: 'openy'
      }
    });
    expect(true).to.equal(true)
  })
});
