#ifndef MMCHAIN_UTILS_H
#define MMCHAIN_UTILS_H
#include <map>
#include <cstdint>
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmchain.h"
namespace protspace {
    void getChainComposition(const MMChain& pChain, std::map<uint16_t,double>& pStart);
    Coords getGeomCenter(const MMChain& pChain);

    /**
     * @brief Convert the Residue list into a FASTA sequence
     * @param wHeader TRUE when you wish the result to start with a header
     * @return chain as a FASTA sequence
     *
     * Convert each Residue into a one letter code. All Residues that are not
     * one of the standard amino acid is ignored. When wHeader is set to true,
     * the sequence start like this : >MOLE_NAME|CHAIN_NAME
     *
     *
     */
    std::string toFASTA(const MMChain& pChain ,const bool& wHeader);
}
#endif // MMCHAIN_UTILS_H

