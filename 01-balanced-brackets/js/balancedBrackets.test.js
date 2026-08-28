const { isValid } = require('./balancedBrackets');

describe('isValid', () => {
  test.each([
    ['(){}[]', true],
    ['[{()}](){}', true],
    ['', true],
    ['hello world', true],
    ['function f(a, b) { return [a, b]; }', true],
    ['[]{()', false],
    ['[{)]', false],
    ['(', false],
    [')', false],
    [')(', false],
    ['(]', false],
  ])('isValid(%j) === %p', (input, expected) => {
    expect(isValid(input)).toBe(expected);
  });
});
