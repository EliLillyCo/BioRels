#include "headers/statics/atomdata.h"
#undef NDEBUG /// Active assertion in release
using namespace std;

namespace protspace
{
unsigned char nameToNum(const std::string& name)
{
    for (unsigned char iAtm=0;iAtm < NNATM; ++iAtm)
    {
        if (name != Periodic[iAtm].name) continue;
        return iAtm;
    }
    return NNATM_OUT;
}


}
