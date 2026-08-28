/**
 * Determines whether a string of brackets is valid.
 *
 * A string is valid when:
 *  - every opening bracket has a matching closing bracket of the same type;
 *  - brackets close in the correct (LIFO) order;
 *  - there are no unmatched brackets left over.
 *
 * Any character that is not one of ()[]{} is ignored, so the function also
 * works fine on strings that mix brackets with other text.
 *
 * @param {string} input
 * @returns {boolean}
 */
function isValid(input) {
  const pairs = {
    ')': '(',
    ']': '[',
    '}': '{',
  };
  const openers = new Set(['(', '[', '{']);

  const stack = [];

  for (const char of input) {
    if (openers.has(char)) {
      stack.push(char);
      continue;
    }

    if (char in pairs) {
      const expectedOpener = stack.pop();
      if (expectedOpener !== pairs[char]) {
        // Either the stack was empty (undefined !== opener)
        // or the top of the stack doesn't match this closer.
        return false;
      }
    }

    // Any other character is simply ignored.
  }

  return stack.length === 0;
}

module.exports = { isValid };
