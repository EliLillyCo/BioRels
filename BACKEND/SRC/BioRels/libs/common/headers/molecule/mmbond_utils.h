#ifndef MMBOND_UTILS_H
#define MMBOND_UTILS_H
#include <cstdint>
#include <string>
namespace protspace
{
class MMBond;
class MMAtom;
const std::string& getBondType(const MMBond& bond);
uint16_t getMMBondType(const std::string bondType);
void removeAllBondsFromAtom(MMAtom& atom);
}

#endif // MMBOND_UTILS_H

